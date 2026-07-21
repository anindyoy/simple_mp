<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

trait InteractsWithGrowthPeriods
{
    use InteractsWithPageFilters;

    /**
     * @return array{0: string, 1: Carbon, 2: Carbon}
     */
    protected function resolveGrowthPeriodBounds(): array
    {
        $period = ($this->pageFilters['period'] ?? null) === 'monthly' ? 'monthly' : 'daily';

        $start = Carbon::parse($this->pageFilters['start_date'] ?? now()->subDays(30)->startOfDay())->startOfDay();
        $end = Carbon::parse($this->pageFilters['end_date'] ?? now())->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $maxStart = $period === 'daily'
            ? $end->copy()->subDays(180)
            : $end->copy()->subMonths(36);

        if ($start->lessThan($maxStart)) {
            $start = $maxStart->startOfDay();
        }

        return [$period, $start, $end];
    }

    /**
     * Bangun data seri "baru per periode" dan "total aktif kumulatif" untuk sebuah model.
     *
     * @return array{labels: array<int, string>, new: array<int, int>, cumulative: array<int, int>}
     */
    protected function buildGrowthSeries(string $modelClass, string $activeColumn = 'is_active'): array
    {
        [$period, $start, $end] = $this->resolveGrowthPeriodBounds();

        $bucketFormat = $period === 'daily' ? 'Y-m-d' : 'Y-m';

        $records = $modelClass::query()
            ->where('created_at', '<=', $end)
            ->get(['created_at', $activeColumn]);

        if ($records->isEmpty()) {
            return ['labels' => [], 'new' => [], 'cumulative' => []];
        }

        // Jangan tampilkan periode sebelum data pertama benar-benar ada, meski range filter lebih awal.
        $earliestCreatedAt = $records->min('created_at');
        $dataStart = $period === 'daily' ? $earliestCreatedAt->copy()->startOfDay() : $earliestCreatedAt->copy()->startOfMonth();

        if ($dataStart->greaterThan($start)) {
            $start = $dataStart;
        }

        if ($start->greaterThan($end)) {
            return ['labels' => [], 'new' => [], 'cumulative' => []];
        }

        $buckets = collect();
        $cursor = $period === 'daily' ? $start->copy()->startOfDay() : $start->copy()->startOfMonth();
        $lastBucket = $period === 'daily' ? $end->copy()->startOfDay() : $end->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($lastBucket)) {
            $buckets->push($cursor->copy());
            $cursor = $period === 'daily' ? $cursor->addDay() : $cursor->addMonth();
        }

        $newByBucket = $records
            ->filter(fn ($record) => $record->created_at->greaterThanOrEqualTo($start))
            ->groupBy(fn ($record) => $record->created_at->format($bucketFormat));

        $activeCreatedAts = $records
            ->filter(fn ($record) => (bool) $record->{$activeColumn})
            ->pluck('created_at')
            ->sort()
            ->values();

        $labels = [];
        $newCounts = [];
        $cumulativeCounts = [];

        foreach ($buckets as $bucketStart) {
            $bucketEnd = $period === 'daily' ? $bucketStart->copy()->endOfDay() : $bucketStart->copy()->endOfMonth();

            $labels[] = $period === 'daily'
                ? $bucketStart->translatedFormat('d M')
                : $bucketStart->translatedFormat('M Y');

            $newCounts[] = $newByBucket->get($bucketStart->format($bucketFormat), collect())->count();
            $cumulativeCounts[] = $activeCreatedAts->filter(fn (Carbon $createdAt) => $createdAt->lessThanOrEqualTo($bucketEnd))->count();
        }

        return [
            'labels' => $labels,
            'new' => $newCounts,
            'cumulative' => $cumulativeCounts,
        ];
    }
}
