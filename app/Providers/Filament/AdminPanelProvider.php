<?php

namespace App\Providers\Filament;

use Filament\Panel;
use App\Models\User;
use Filament\PanelProvider;
use Filament\Pages\Dashboard;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Support\Facades\Schema;
use Filament\Widgets\FilamentInfoWidget;
use Filament\Http\Middleware\Authenticate;
use App\Filament\Widgets\PushCountdownWidget;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Http\Middleware\EnsureLapakProfileExists;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panelConfig = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->maxContentWidth('full')
            ->login()
            ->databaseTransactions()
            ->databaseNotifications()
            ->databaseNotificationsPolling('15s')
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn() => view('filament.topbar.scripts')
            )
            ->colors([
                'primary' => Color::hex('#3F9AAE'),
                'secondary' => Color::hex('#79C9C5'),
                'accent' => Color::hex('#FFE2AF'),
                'other' => Color::hex('#F96E5B'),
                'info' => Color::hex('#79C9C5'),
                'warning' => Color::hex('#FFE2AF'),
                'danger' => Color::hex('#F96E5B'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                PushCountdownWidget::class,
                AccountWidget::class,
                FilamentInfoWidget::class,
            ]);

        if (
            app()->environment('local') &&
            !app()->runningUnitTests() &&
            Schema::hasTable('users') &&
            Schema::hasColumn('users', 'is_admin')
        ) {
            $panelConfig->plugins([
                FilamentDeveloperLoginsPlugin::make()
                    ->enabled(true)
                    ->users(
                        collect([
                            'Admin' => User::where('is_admin', true)->pluck('email')->first(),
                            'User' => User::whereHas('lapak')->pluck('email')->first(),
                        ])->filter()->toArray()
                    )
            ]);
        }

        return $panelConfig
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                EnsureLapakProfileExists::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
