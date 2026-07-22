<?php

namespace App\Services;

use App\Models\ProductView;
use App\Models\VisitorStats;
use Illuminate\Support\Facades\Cache;

class VisitorStatsService
{
    private const CACHE_KEY = 'stats.visitors_24h';

    private const CACHE_TTL_HOURS = 6;

    public static function calculate(): int
    {
        $count = ProductView::query()
            ->where('created_at', '>=', now()->subHours(24))
            ->distinct('ip_address')
            ->count('ip_address');

        Cache::put(self::CACHE_KEY, $count, now()->addHours(self::CACHE_TTL_HOURS));

        return $count;
    }

    public static function getCached(): int
    {
        return Cache::get(self::CACHE_KEY) ?? self::calculate();
    }

    /**
     * Record today's unique visitor count into the visitor_stats table.
     * This is meant to be called once per day (e.g., via scheduler at 23:59).
     */
    public static function recordDaily(): void
    {
        $today = now()->startOfDay();

        $count = ProductView::query()
            ->where('created_at', '>=', $today)
            ->where('created_at', '<', $today->copy()->addDay())
            ->distinct('ip_address')
            ->count('ip_address');

        VisitorStats::updateOrCreate(
            ['date' => $today->toDateString()],
            ['visitor_count' => $count]
        );
    }
}