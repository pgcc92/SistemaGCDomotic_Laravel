<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Auth\RemoteAuthService;
use App\Domain\Auth\UserTokenService;
use App\Domain\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthLoginController
{
    public function __construct(
        private readonly RemoteAuthService $auth,
        private readonly UserTokenService $tokens,
        private readonly TenantContext $tenant,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'documento' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'max:120'],
        ]);

        try {
            $res = $this->auth->attemptLogin($validated['documento'], $validated['password']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        $user = $res['user'];
        $token = $this->tokens->mint([
            'uid' => (int) $user->id,
            'tenant' => $this->tenant->tenantId(),
            'exp' => time() + (60 * 60 * 8), // 8h
        ]);

        return response()->json([
            'ok' => true,
            'data' => [
                'token' => $token,
                'usuario' => [
                    'id' => (int) $user->id,
                    'numero_documento' => (string) $user->numero_documento,
                    'nombre' => (string) $user->nombre,
                    'email' => $user->email,
                    'rol_id' => $user->rol_id,
                    'sucursal_id' => $user->sucursal_id,
                    'tecnico_id' => $user->tecnico_id,
                    'twofa_enabled' => (bool) $user->twofa_enabled,
                ],
            ],
        ]);
    }
}

