<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithGrowthPeriods;
use App\Models\LapakProfile;
use Filament\Widgets\Widget;

class LapakGrowthTableWidget extends Widget
{
    use InteractsWithGrowthPeriods;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.lapak-growth-table-widget';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    protected function getViewData(): array
    {
        return [
            'series' => $this->buildGrowthSeries(LapakProfile::class),
        ];
    }
}
