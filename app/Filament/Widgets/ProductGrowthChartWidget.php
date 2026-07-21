<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithGrowthPeriods;
use App\Models\Product;
use Filament\Widgets\ChartWidget;

class ProductGrowthChartWidget extends ChartWidget
{
    use InteractsWithGrowthPeriods;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Produk Aktif';

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
        $series = $this->buildGrowthSeries(Product::class);

        return [
            'datasets' => [
                [
                    'type' => 'line',
                    'label' => 'Total Produk Aktif (Kumulatif)',
                    'data' => $series['cumulative'],
                    'borderColor' => '#F96E5B',
                    'backgroundColor' => '#F96E5B',
                    'tension' => 0.3,
                    'fill' => false,
                ],
                [
                    'type' => 'bar',
                    'label' => 'Produk Baru per Periode',
                    'data' => $series['new'],
                    'backgroundColor' => '#79C9C5',
                ],
            ],
            'labels' => $series['labels'],
        ];
    }
}
