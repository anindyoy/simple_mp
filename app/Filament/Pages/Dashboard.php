<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\LapakGrowthChartWidget;
use App\Filament\Widgets\LapakGrowthTableWidget;
use App\Filament\Widgets\ProductGrowthChartWidget;
use App\Filament\Widgets\ProductGrowthTableWidget;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user->is_admin && ! $user->lapak) {
            Notification::make('no-lapak-profile')
                ->title('Lapak belum dibuat')
                ->body('Anda belum memiliki profil lapak. Buat profil lapak terlebih dahulu untuk mengelola produk Anda.')
                ->warning()
                ->actions([
                    Action::make('create')
                        ->label('Buat Lapak Sekarang')
                        ->url(route('filament.admin.pages.lapak-profile')),
                ])
                ->send();
        }
    }

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
