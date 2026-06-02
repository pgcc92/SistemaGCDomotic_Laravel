<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class AuthMeController
{
    public function __construct(
        private readonly TenantContext $tenant,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $uid = (int) request()?->attributes->get('remote_uid', 0);
        if ($uid > 0) {
            $u = DB::table('usuarios')->where('id', $uid)->first();
            if (!$u) {
                return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
            }

            return response()->json([
                'ok' => true,
                'tenant' => $this->tenant->tenantId(),
                'usuario' => [
                    'id' => (int) $u->id,
                    'numero_documento' => (string) $u->numero_documento,
                    'nombre' => (string) $u->nombre,
                    'email' => $u->email,
                    'rol_id' => $u->rol_id,
                    'sucursal_id' => $u->sucursal_id,
                    'tecnico_id' => $u->tecnico_id,
                    'twofa_enabled' => (bool) $u->twofa_enabled,
                ],
            ]);
        }

        return response()->json([
            'ok' => true,
            'tenant' => $this->tenant->tenantId(),
            'auth' => 'api_token',
        ]);
    }
}
