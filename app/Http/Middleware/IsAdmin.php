<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         if (Auth::check()) {
            if (Auth::user()->status === 'admin') {
                return $next($request);
            }
            if (Auth::user()->allow_aopod_death === 'Y' && ($request->is('admin/aopod') || $request->is('admin/aopod/death-check'))) {
                return $next($request);
            }
        }

        abort(403, 'Access denied');
    }
}
