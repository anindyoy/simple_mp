<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithGrowthPeriods;
use App\Models\LapakProfile;
use Filament\Widgets\ChartWidget;

class LapakGrowthChartWidget extends ChartWidget
{
    use InteractsWithGrowthPeriods;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Lapak Aktif';

    protected string $view = 'filament.widgets.bare-chart-widget';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $series = $this->buildGrowthSeries(LapakProfile::class);

        return [
            'datasets' => [
                [
                    'type' => 'line',
                    'label' => 'Total Lapak Aktif (Kumulatif)',
                    'data' => $series['cumulative'],
                    'borderColor' => '#3F9AAE',
                    'backgroundColor' => '#3F9AAE',
                    'tension' => 0.3,
                    'fill' => false,
                ],
                [
                    'type' => 'bar',
                    'label' => 'Lapak Baru per Periode',
                    'data' => $series['new'],
                    'backgroundColor' => '#FFE2AF',
                ],
            ],
            'labels' => $series['labels'],
        ];
    }
}
