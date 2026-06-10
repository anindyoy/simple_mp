<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Product;
use App\Models\LapakProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProductScheduleService
{
    const DELAY_HOURS = 4;

    // TTL eligible IDs — pendek karena schedule bisa "unlock" tiap menit
    private const ELIGIBLE_TTL = 60;
    private const ELIGIBLE_KEY = 'products.schedule.eligible_ids';
    private static bool $bypassThrottle = false;

    // ─────────────────────────────────────────
    // Method asli (tidak diubah logicnya)
    // ─────────────────────────────────────────

    public static function rebuild(int $lapakId): void
    {
        $products = Product::query()
            ->where('lapak_id', $lapakId)
            ->orderBy('created_at', 'asc')
            ->get();

        $schedule = self::buildSchedule($products);

        Cache::put(
            self::cacheKey($lapakId),
            $schedule,
            now()->addDays(1)
        );

        // Invalidate eligible cache setelah rebuild
        self::forgetEligible();
    }

    public static function append(Product $product): void
    {
        $lapakId = $product->lapak_id;

        $schedule = Cache::get(self::cacheKey($lapakId), []);

        $lastPublish = collect($schedule)->max();
        $createdAt = Carbon::parse($product->created_at);

        if (! $lastPublish) {
            $publishAt = $createdAt;
        } else {
            $next = Carbon::parse($lastPublish)->addHours(self::DELAY_HOURS);
            $publishAt = $next->gt($createdAt) ? $next : $createdAt;
        }

        $schedule[$product->id] = $publishAt;

        Cache::put(self::cacheKey($lapakId), $schedule, now()->addDays(1));

        self::forgetEligible();
    }

    public static function get(int $lapakId): array
    {
        return Cache::get(self::cacheKey($lapakId), []);
    }

    public static function cacheKey(int $lapakId): string
    {
        return "products.schedule.{$lapakId}";
    }

    // ─────────────────────────────────────────
    // Method baru: forget (belum ada di class asli)
    // ─────────────────────────────────────────

    /**
     * Hapus schedule cache untuk satu lapak + invalidate eligible cache.
     * Dipanggil dari Observer saat produk/lapak berubah.
     */
    public static function forget(int $lapakId): void
    {
        Cache::forget(self::cacheKey($lapakId));
        self::forgetEligible();
    }

    // ─────────────────────────────────────────
    // Method baru: Eligible Product IDs
    // ─────────────────────────────────────────

    /**
     * Kembalikan collection of product_id yang sudah boleh tampil sekarang.
     * Di-cache 60 detik agar tidak rebuild tiap request.
     */
    public static function getEligibleProductIds(): \Illuminate\Support\Collection
    {
        return Cache::remember(self::ELIGIBLE_KEY, self::ELIGIBLE_TTL, function () {
            return self::resolveEligibleProductIds();
        });
    }

    public static function forgetEligible(): void
    {
        Cache::forget(self::ELIGIBLE_KEY);
    }

    // ─────────────────────────────────────────
    // Internal
    // ─────────────────────────────────────────

    private static function resolveEligibleProductIds(): \Illuminate\Support\Collection
    {
        $activeLapakIds = LapakProfile::query()
            ->where('is_active', true)
            ->pluck('id');

        $now = now();
        $eligible = collect();
        $productsByLapak = Product::query()
            ->whereIn('lapak_id', $activeLapakIds)
            ->orderBy('lapak_id')
            ->orderBy('created_at')
            ->get()
            ->groupBy('lapak_id');

        foreach ($activeLapakIds as $lapakId) {
            $schedule = self::buildSchedule(
                $productsByLapak->get($lapakId, collect())
            );

            \Log::debug('bypassThrottle flag', ['value' => self::$bypassThrottle]);
            \Log::debug('Schedule lapak ' . $lapakId, $schedule);

            $lapakEligible = collect($schedule)
                ->filter(fn($publishAt) => Carbon::parse($publishAt) <= $now)
                ->keys();

            \Log::debug('Eligible lapak ' . $lapakId, $lapakEligible->toArray());

            $eligible = $eligible->merge($lapakEligible);
        }

        return $eligible->unique()->values();
    }

    public static function withoutThrottle(callable $callback): void
    {
        self::$bypassThrottle = true;
        try {
            $callback();
        } finally {
            self::$bypassThrottle = false; // selalu reset meski exception
        }
    }

    private static function buildSchedule(Collection $products): array
    {
        $schedule = [];
        $lastPublishAt = null;

        foreach ($products as $product) {
            $createdAt = Carbon::parse($product->created_at);

            if ($lastPublishAt === null) {
                $publishAt = $createdAt;
            } else {
                $next = $lastPublishAt->copy()->addHours(self::DELAY_HOURS);
                $publishAt = $next->gt($createdAt) ? $next : $createdAt;
            }

            $schedule[$product->id] = $publishAt;
            $lastPublishAt = $publishAt;
        }

        return $schedule;
    }
}
