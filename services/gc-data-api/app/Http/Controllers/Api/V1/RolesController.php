<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Rbac\RbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class RolesController
{
    public function __construct(
        private readonly RbacService $rbac,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        if ($uid <= 0 || !$this->rbac->can($uid, 'roles', 'ver')) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $rows = DB::table('roles')->orderBy('id')->get();
        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        if ($uid <= 0 || !$this->rbac->can($uid, 'roles', 'crear')) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $payload = $request->validate([
            'codigo' => ['required', 'string', 'max:30'],
            'nombre' => ['required', 'string', 'max:80'],
            'protegido' => ['nullable', 'boolean'],
        ]);

        $codigo = (string) $payload['codigo'];
        $codigo = preg_replace('/[^a-zA-Z0-9_-]/', '', $codigo) ?: $codigo;

        $id = DB::table('roles')->insertGetId([
            'codigo' => $codigo,
            'nombre' => (string) $payload['nombre'],
            'protegido' => (bool) ($payload['protegido'] ?? false),
        ]);

        return response()->json(['ok' => true, 'data' => DB::table('roles')->where('id', $id)->first()], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        if ($uid <= 0 || !$this->rbac->can($uid, 'roles', 'editar')) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $role = DB::table('roles')->where('id', $id)->first();
        if (!$role) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }
        if ((bool) ($role->protegido ?? false)) {
            return response()->json(['ok' => false, 'error' => 'Rol protegido.'], 422);
        }

        $payload = $request->validate([
            'codigo' => ['required', 'string', 'max:30'],
            'nombre' => ['required', 'string', 'max:80'],
        ]);

        $codigo = (string) $payload['codigo'];
        $codigo = preg_replace('/[^a-zA-Z0-9_-]/', '', $codigo) ?: $codigo;

        DB::table('roles')->where('id', $id)->update([
            'codigo' => $codigo,
            'nombre' => (string) $payload['nombre'],
        ]);

        return response()->json(['ok' => true, 'data' => DB::table('roles')->where('id', $id)->first()]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        if ($uid <= 0 || !$this->rbac->can($uid, 'roles', 'eliminar')) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $role = DB::table('roles')->where('id', $id)->first();
        if (!$role) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }
        if ((bool) ($role->protegido ?? false)) {
            return response()->json(['ok' => false, 'error' => 'Rol protegido.'], 422);
        }

        $inUse = DB::table('usuarios')->where('rol_id', $id)->exists();
        if ($inUse) {
            return response()->json(['ok' => false, 'error' => 'No se puede eliminar: hay usuarios con este rol.'], 422);
        }

        DB::table('roles')->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }
}
