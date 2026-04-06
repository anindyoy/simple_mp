<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductScheduleService
{
    const DELAY_HOURS = 4;

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
    }

    public static function append(Product $product): void
    {
        $lapakId = $product->lapak_id;

        $schedule = Cache::get(self::cacheKey($lapakId), []);

        // Ambil publish terakhir dari cache
        $lastPublish = collect($schedule)->max();

        if (! $lastPublish) {
            $publishAt = $product->created_at;
        } else {
            $publishAt = \Carbon\Carbon::parse($lastPublish)->addHours(4);
        }

        // Tambahkan TANPA mengubah yang lama
        $schedule[$product->id] = $publishAt;

        Cache::put(self::cacheKey($lapakId), $schedule, now()->addDays(1));
    }

    public static function get(int $lapakId): array
    {
        return Cache::get(self::cacheKey($lapakId), []);
    }

    public static function cacheKey(int $lapakId): string
    {
        return "products.schedule.{$lapakId}";
    }
}
