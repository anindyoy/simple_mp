<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureLapakProfileExists
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Hanya berlaku untuk user yang sudah login
        if (!$user) {
            return $next($request);
        }

        // Hanya untuk user non-admin (is_admin = 0)
        if ($user->is_admin) {
            return $next($request);
        }

        // Route yang dikecualikan dari pengecekan
        $excludedRoutes = [
            'filament.admin.pages.lapak-profile',
            'logout',
        ];

        if (in_array($request->route()?->getName(), $excludedRoutes)) {
            return $next($request);
        }

        // Cek apakah user sudah memiliki lapak profile
        if (!$user->lapak) {
            return redirect()->route('filament.admin.pages.lapak-profile')
                ->with('message', 'Anda harus membuat profil lapak terlebih dahulu sebelum melanjutkan.');
        }

        return $next($request);
    }
}
