<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\LapakGrowthChartWidget;
use App\Filament\Widgets\LapakGrowthTableWidget;
use App\Filament\Widgets\ProductGrowthChartWidget;
use App\Filament\Widgets\ProductGrowthTableWidget;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    /**
     * @return array<class-string<\Filament\Widgets\Widget>>
     */
    public function getWidgets(): array
    {
        $widgets = parent::getWidgets();

        // Remove growth report widgets — they belong on the GrowthReportPage only.
        $excluded = [
            LapakGrowthChartWidget::class,
            LapakGrowthTableWidget::class,
            ProductGrowthChartWidget::class,
            ProductGrowthTableWidget::class,
        ];

        return array_values(array_filter($widgets, fn (string $widget) => ! in_array($widget, $excluded, true)));
    }
}