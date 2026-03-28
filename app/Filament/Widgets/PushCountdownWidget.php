<?php

namespace App\Filament\Widgets;

use Livewire\Attributes\On;
use Filament\Widgets\Widget;
use App\Policies\ProductPolicy;

class PushCountdownWidget extends Widget
{
    protected static ?int $sort = 1;

    protected string $view = 'filament.widgets.push-countdown-widget';

    public int $refreshNonce = 0;

    public static function canView(): bool
    {
        return auth()->check() && ! auth()->user()->is_admin;
    }

    #[On('push-countdown-refresh')]
    public function refreshWidget(): void
    {
        $this->refreshNonce++;
    }

    protected function getViewData(): array
    {
        return [
            'pushTokens' => ProductPolicy::currentPushTokens(),
            'nextPushAtLabel' => ProductPolicy::formattedNextPushAt(),
            'remainingSeconds' => ProductPolicy::remainingPushCooldownSeconds(),
            'refreshNonce' => $this->refreshNonce,
        ];
    }
}