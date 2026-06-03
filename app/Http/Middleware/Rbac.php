<?php

namespace App\Http\Middleware;

use App\Infrastructure\Remote\RemoteRbacClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class Rbac
{
    public function __construct(
        private readonly RemoteRbacClient $rbac,
    ) {
    }

    public function handle(Request $request, Closure $next, string $modulo, string $accion): Response
    {
        if (app()->environment('testing')) {
            return $next($request);
        }

        $perms = $this->rbac->myPermissions();

        $allowed = (bool) (($perms['*']['*'] ?? false) || ($perms[$modulo][$accion] ?? false));
        if (!$allowed) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'error' => "No tienes permiso para {$accion} en {$modulo}.",
                ], 403);
            }

            abort(403);
        }

        return $next($request);
    }
}
