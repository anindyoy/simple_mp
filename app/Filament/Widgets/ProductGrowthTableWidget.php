<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithGrowthPeriods;
use App\Models\Product;
use Filament\Widgets\Widget;

class ProductGrowthTableWidget extends Widget
{
    use InteractsWithGrowthPeriods;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.product-growth-table-widget';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    protected function getViewData(): array
    {
        return [
            'series' => $this->buildGrowthSeries(Product::class),
        ];
    }
}
