<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Guest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $guard
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $guard = null): Response
    {
        // Determine the guard to use
        if (!$guard && $request->is('admin/*')) {
            $guard = 'admin';
        }

        if (Auth::guard($guard)->check()) {
            $home = $guard == 'admin' ? 'admin.dashboard' : 'home';
            return redirect()->route($home);
        }

        return $next($request);
    }
}
