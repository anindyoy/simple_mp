<?php

namespace App\Providers;

use Filament\View\PanelsRenderHook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LogoutResponse::class, function () {
            return new class implements LogoutResponse {
                public function toResponse($request): RedirectResponse
                {
                    return redirect('/');
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_AFTER,
            fn(): string => Blade::render(
                <<<BLADE
            <div class="fi-topbar-item">
                <a
                    href="/"
                    target="_blank"
                    title="Halaman Utama"
                    class="fi-btn fi-btn-color-gray fi-btn-size-sm"
                >
                    <svg class="fi-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"
                        />
                    </svg>

                    <span>Halaman Utama</span>
                </a>
            </div>
            BLADE
            )
        );
    }
}
