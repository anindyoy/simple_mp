<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\VisitorStats;
use Filament\Widgets\ChartWidget;

class VisitorStatsChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Pengunjung Harian';

    public ?string $filter = 'daily';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilters(): ?array
    {
        return [
            'daily' => 'Harian',
            'monthly' => 'Rata-rata Bulanan',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter;

        if ($filter === 'monthly') {
            return $this->getMonthlyAverageData();
        }

        return $this->getDailyData();
    }

    private function getDailyData(): array
    {
        $records = VisitorStats::query()
            ->where('date', '>=', now()->subDays(30)->startOfDay())
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Pengunjung',
                    'data' => $records->pluck('visitor_count')->all(),
                    'backgroundColor' => '#3F9AAE',
                    'borderColor' => '#3F9AAE',
                ],
            ],
            'labels' => $records->map(fn (VisitorStats $record) => Carbon::parse($record->date)->translatedFormat('d M'))->all(),
        ];
    }

    private function getMonthlyAverageData(): array
    {
        $records = VisitorStats::query()
            ->where('date', '>=', now()->subMonths(12)->startOfMonth())
            ->orderBy('date')
            ->get()
            ->groupBy(fn (VisitorStats $record) => Carbon::parse($record->date)->format('Y-m'));

        $months = collect(range(11, 0))->map(fn (int $i) => now()->subMonths($i)->startOfMonth());

        $averages = $months->map(function ($month) use ($records) {
            $monthRecords = $records->get($month->format('Y-m'), collect());

            if ($monthRecords->isEmpty()) {
                return 0;
            }

            return (int) round($monthRecords->avg('visitor_count'));
        });

        return [
            'datasets' => [
                [
                    'label' => 'Rata-rata Pengunjung per Hari',
                    'data' => $averages->all(),
                    'backgroundColor' => '#79C9C5',
                    'borderColor' => '#79C9C5',
                ],
            ],
            'labels' => $months->map(fn ($month) => $month->translatedFormat('M Y'))->all(),
        ];
    }
}