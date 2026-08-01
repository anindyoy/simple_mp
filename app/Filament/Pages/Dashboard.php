<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\LapakGrowthChartWidget;
use App\Filament\Widgets\LapakGrowthTableWidget;
use App\Filament\Widgets\ProductGrowthChartWidget;
use App\Filament\Widgets\ProductGrowthTableWidget;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    public function mount(): void
    {
        $user = auth()->user();
    }

    protected function getHeaderActions(): array
    {
        // Only show button in local or demo mode AND for admin users
        if (! app()->environment('local') && ! config('app.demo_mode', false)) {
            return [];
        }

        if (! auth()->user()?->is_admin) {
            return [];
        }

        return [
            Action::make('runUpdateCatalogSeeder')
                ->label('Update Catalog')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Update Catalog')
                ->modalDescription('Apakah Anda yakin ingin menjalankan UpdateCatalogSeeder? Proses ini akan membuat atau memperbarui data produk secara acak.')
                ->modalSubmitActionLabel('Ya, Jalankan')
                ->action(function () {
                    $this->runUpdateCatalogSeeder();
                }),
        ];
    }

    public function runUpdateCatalogSeeder(): void
    {
        try {
            // Call Artisan command directly instead of HTTP request
            // Use --quiet to suppress STDOUT output from seeder
            \Artisan::call('db:seed', [
                '--class' => \Database\Seeders\UpdateCatalogSeeder::class,
                '--force' => true,
                '--quiet' => true,
            ]);

            Notification::make()
                ->title('Seeder berhasil dijalankan!')
                ->body('UpdateCatalogSeeder telah berhasil dijalankan.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal menjalankan seeder')
                ->body('Error: ' . $e->getMessage())
                ->danger()
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
