<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('gc.api.token');
        $hashDriver = (string) config('gc.api.hash_driver', 'sha256');

        $provided = trim((string) $request->header('X-API-Key', ''));
        if ($provided === '') {
            $auth = (string) $request->header('Authorization', '');
            if (preg_match('/^Bearer\\s+(?<t>.+)$/i', $auth, $m)) {
                $provided = trim((string) ($m['t'] ?? ''));
            }
        }

        if ($provided === '') {
            return response()->json([
                'ok' => false,
                'error' => 'Unauthorized',
            ], 401);
        }

        // Modo simple (solo env) para despliegues rápidos.
        if ($expected !== '' && hash_equals($expected, $provided)) {
            // En modo env no tenemos created_by. Usamos un "usuario técnico" si fue configurado,
            // o dejamos 0 (sin RBAC) para endpoints que no lo requieran.
            $uid = (int) config('gc.api.created_by', 0);
            if ($uid > 0) {
                $request->attributes->set('remote_uid', $uid);
            }
            return $next($request);
        }

        // Modo recomendado: API keys hasheadas en tabla `api_keys` (rotación/revocación).
        $apiKeysTable = 'api_keys';
        $now = now();

        $hash = match ($hashDriver) {
            'hmac_sha256' => hash_hmac('sha256', $provided, (string) config('app.key')),
            default => hash('sha256', $provided),
        };

        $row = DB::table($apiKeysTable)
            ->where('key_hash', $hash)
            ->where('activo', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('expira_at')->orWhere('expira_at', '>', $now);
            })
            ->first();

        if (!$row) {
            return response()->json([
                'ok' => false,
                'error' => 'Unauthorized',
            ], 401);
        }

        DB::table($apiKeysTable)->where('id', $row->id)->update(['ultimo_uso' => $now]);

        // Para endpoints protegidos por RBAC, propagamos un usuario "dueño" de la key.
        $createdBy = (int) ($row->created_by ?? 0);
        if ($createdBy > 0) {
            $request->attributes->set('remote_uid', $createdBy);
        }

        return $next($request);
    }
}
