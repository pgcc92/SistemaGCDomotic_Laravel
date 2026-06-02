<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class SessionFingerprint
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing')) {
            return $next($request);
        }

        // Solo para rutas protegidas (cuando ya hay sesión).
        if (Auth::check()) {
            $current = hash('sha256', ($request->ip() ?? '').'|'.((string) $request->userAgent()));
            $stored = (string) $request->session()->get('_fp', '');

            if ($stored === '') {
                $request->session()->put('_fp', $current);
            } elseif (!hash_equals($stored, $current)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect('/login');
            }
        }

        return $next($request);
    }
}
