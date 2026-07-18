<?php

namespace App\Providers\Filament;

use Filament\Panel;
use App\Models\User;
use Filament\PanelProvider;
use Filament\Pages\Dashboard;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Schema;
use Filament\Http\Middleware\Authenticate;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use App\Filament\Widgets\PushCountdownWidget;
use JibayMcs\FilamentTour\FilamentTourPlugin;
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
use JeffersonGoncalves\WhatsappWidget\Models\WhatsappAgent;
use App\Livewire\UpdatePassword;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $plugins = [];

        $plugins[] = BreezyCore::make()
            ->myProfile()
            ->myProfileComponents(['update_password' => UpdatePassword::class])
            ->enableTwoFactorAuthentication(
                condition: (bool) config('services.filament.two_factor_enabled', true),
                force: fn(): bool => app()->environment('production') && (bool) auth()->user()?->is_admin,
            );

        $plugins[] = FilamentTourPlugin::make()->enableCssSelector(app()->environment('local'));
        if (
            app()->environment('local') &&
            !app()->runningUnitTests() &&
            config('services.filament.developer_logins', false) &&
            Schema::hasTable('users') &&
            Schema::hasColumn('users', 'is_admin')
        ) {
            $plugins[] = FilamentDeveloperLoginsPlugin::make()
                ->enabled(true)
                ->users(
                    collect([
                        'Admin' => User::where('is_admin', true)->pluck('email')->first(),
                        'User' => User::whereHas('lapak')->pluck('email')->first(),
                    ])->filter()->toArray()
                );
        }

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->maxContentWidth('full')
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->brandLogo(asset('img/logo-transparent.png'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('img/favicon-32x32.png'))
            ->databaseTransactions()
            ->databaseNotifications()
            ->databaseNotificationsPolling('15s')
            ->navigationItems([])
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn() => view('filament.topbar.scripts')
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_END,
                fn() => view('livewire.hubungi-admin-modal')
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
            ])
            ->plugins($plugins)
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
