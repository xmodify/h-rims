<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MophAlert2FAMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. If user is not logged in, let standard auth middleware handle it
        if (!auth()->check()) {
            return $next($request);
        }

        // 2. Exclude authentication and 2FA verification pages from redirection
        if ($request->routeIs('auth.2fa.*') || $request->is('login/verify-2fa*') || $request->is('logout') || $request->is('login')) {
            return $next($request);
        }

        // 3. Check if Moph Alert 2FA is active in system settings or central license server
        $mophAlertActive = \App\Services\LicenseVerificationService::getConfig('moph_alert_active', 'moph_alert_active');
        if ($mophAlertActive !== 'Y') {
            return $next($request);
        }

        // 4. If logged in but 2FA is not verified yet, redirect to verification page
        if (session('moph_alert_2fa_verified') === false) {
            return redirect()->route('auth.2fa.index');
        }

        return $next($request);
    }
}
