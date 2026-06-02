<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Authz\SuperAdminService;
use App\Domain\Rbac\RbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class PermisosUpdateController
{
    public function __construct(
        private readonly RbacService $rbac,
        private readonly SuperAdminService $superAdmin,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        if ($uid <= 0) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
        }

        $isAdminRole = $this->rbac->isAdmin($uid);
        if (!$this->superAdmin->canManagePermissions($uid, $isAdminRole)) {
            return response()->json(['ok' => false, 'error' => 'Solo el administrador principal puede editar permisos.'], 403);
        }

        // Reclamar ownership si aún no existe.
        $this->superAdmin->claimIfEmpty($uid, $isAdminRole);

        if (!$this->rbac->can($uid, 'permisos', 'editar')) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $payload = $request->validate([
            'changes' => ['required', 'array', 'min:1'],
            'changes.*.rol_id' => ['required', 'integer', 'min:1'],
            'changes.*.modulo_id' => ['required', 'integer', 'min:1'],
            'changes.*.accion_id' => ['required', 'integer', 'min:1'],
            'changes.*.permitido' => ['required', 'boolean'],
        ]);

        $changes = (array) ($payload['changes'] ?? []);

        DB::transaction(function () use ($changes, $uid, $request) {
            foreach ($changes as $c) {
                DB::table('rol_permisos')->updateOrInsert(
                    [
                        'rol_id' => (int) $c['rol_id'],
                        'modulo_id' => (int) $c['modulo_id'],
                        'accion_id' => (int) $c['accion_id'],
                    ],
                    [
                        'permitido' => (bool) $c['permitido'],
                    ],
                );
            }

            DB::table('audit_log')->insert([
                'usuario_id' => $uid,
                'accion' => 'permisos_updated',
                'entidad' => 'rol_permisos',
                'entidad_id' => null,
                'payload' => DB::raw("'{}'::jsonb"),
                'ip' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return response()->json(['ok' => true]);
    }
}
