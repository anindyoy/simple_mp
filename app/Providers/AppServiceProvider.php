<?php

namespace App\Providers;

use Carbon\Carbon;
use App\Models\Product;
use App\Models\LapakProfile;
use App\Models\TutorialPage;
use Filament\Facades\Filament;
use Filament\Support\Assets\Js;
use App\Observers\ProductObserver;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Policies\WhatsappAgentPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use App\Observers\LapakProfileObserver;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Facades\FilamentAsset;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use JeffersonGoncalves\WhatsappWidget\Models\WhatsappAgent;

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
        Product::observe(ProductObserver::class);
        LapakProfile::observe(LapakProfileObserver::class);
        Gate::policy(WhatsappAgent::class, WhatsappAgentPolicy::class);

        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_AFTER,
            function (): string {

                $url = '/' . ltrim(Request::path(), '/');

                $hasTutorial = Cache::remember(
                    'tutorial_exists_' . md5($url),
                    now()->addMinutes(5),
                    fn() => TutorialPage::where('url', $url)
                        ->whereHas('images')
                        ->exists()
                );

                return view('filament.topbar.actions', [
                    'hasTutorial' => $hasTutorial,
                ])->render();
            }
        );
    }
}
