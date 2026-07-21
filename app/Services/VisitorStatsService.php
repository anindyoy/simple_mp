<?php

namespace App\Services;

use App\Models\ProductView;
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
}
