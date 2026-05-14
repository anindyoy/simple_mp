<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductScheduleService
{
    const DELAY_HOURS = 4;

    // TTL eligible IDs — pendek karena schedule bisa "unlock" tiap menit
    private const ELIGIBLE_TTL = 60;
    private const ELIGIBLE_KEY = 'products.schedule.eligible_ids';

    // ─────────────────────────────────────────
    // Method asli (tidak diubah logicnya)
    // ─────────────────────────────────────────

    public static function rebuild(int $lapakId): void
    {
        $products = Product::where('lapak_id', $lapakId)
            ->orderBy('created_at', 'asc')
            ->get();

        $schedule = [];

        foreach ($products as $index => $product) {
            $publishAt = Carbon::parse($product->created_at)
                ->addHours($index * self::DELAY_HOURS);

            $schedule[$product->id] = $publishAt;
        }

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

        if (! $lastPublish) {
            $publishAt = $product->created_at;
        } else {
            $publishAt = Carbon::parse($lastPublish)->addHours(4);
        }

        $schedule[$product->id] = $publishAt;

        Cache::put(self::cacheKey($lapakId), $schedule, now()->addDays(1));

        // Invalidate eligible cache setelah ada produk baru ditambah
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
        $activeLapakIds = \App\Models\LapakProfile::where('is_active', true)->pluck('id');

        $now = now();
        $eligible = collect();

        foreach ($activeLapakIds as $lapakId) {
            $schedule = self::get($lapakId);

            // Rebuild kalau kosong — ikuti logic asli
            if (empty($schedule)) {
                self::rebuild($lapakId);
                $schedule = self::get($lapakId);
            }

            $lapakEligible = collect($schedule)
                ->filter(fn($publishAt) => Carbon::parse($publishAt) <= $now)
                ->keys(); // product_id

            $eligible = $eligible->merge($lapakEligible);
        }

        return $eligible->unique()->values();
    }
}