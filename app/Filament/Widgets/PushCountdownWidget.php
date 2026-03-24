<?php

namespace App\Filament\Widgets;

use App\Policies\ProductPolicy;
use Filament\Widgets\Widget;

class PushCountdownWidget extends Widget
{
    protected static ?int $sort = 1;

    protected string $view = 'filament.widgets.push-countdown-widget';

    public static function canView(): bool
    {
        return auth()->check() && ! auth()->user()->is_admin;
    }

    protected function getViewData(): array
    {
        return [
            'nextPushAtLabel' => ProductPolicy::formattedNextPushAt(),
            'remainingSeconds' => ProductPolicy::remainingPushCooldownSeconds(),
        ];
    }
}