<?php

namespace App\Http\Middleware;

use App\Domain\Auth\UserTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiAuthMiddleware
{
    public function __construct(
        private readonly UserTokenService $tokens,
        private readonly ApiKeyMiddleware $apiKey,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // 1) Si es token de usuario (u1.*) lo validamos aquí.
        $auth = (string) $request->header('Authorization', '');
        if (preg_match('/^Bearer\\s+(?<t>.+)$/i', $auth, $m)) {
            $t = trim((string) ($m['t'] ?? ''));
            if (str_starts_with($t, UserTokenService::PREFIX)) {
                try {
                    $claims = $this->tokens->parse($t);
                    if ($claims['uid'] <= 0 || $claims['exp'] <= 0 || time() >= $claims['exp']) {
                        return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
                    }
                    $request->attributes->set('remote_uid', $claims['uid']);
                    $request->attributes->set('remote_tenant', $claims['tenant']);
                    return $next($request);
                } catch (\Throwable) {
                    return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
                }
            }
        }

        // 2) Fallback: API key (n8n / integraciones)
        return $this->apiKey->handle($request, $next);
    }
}

