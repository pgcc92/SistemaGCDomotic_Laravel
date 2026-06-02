<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SucursalesController
{
    public function index(Request $request): JsonResponse
    {
        $limit = max(1, min(200, (int) $request->query('limit', 50)));
        $rows = DB::table('sucursales')->orderBy('id', 'desc')->limit($limit)->get();
        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        $user = $uid > 0 ? DB::table('usuarios')->leftJoin('roles', 'roles.id', '=', 'usuarios.rol_id')->where('usuarios.id', $uid)->select(['roles.codigo as rol_codigo'])->first() : null;
        $isAdmin = $user && (string) ($user->rol_codigo ?? '') === 'administrador';
        if (!$isAdmin) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $payload = $request->validate([
            'codigo' => ['required', 'string', 'max:20'],
            'nombre' => ['required', 'string', 'max:120'],
            'direccion' => ['nullable', 'string'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'ciudad' => ['nullable', 'string', 'max:80'],
            'encargado_id' => ['nullable', 'integer', 'min:1'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $id = (int) DB::table('sucursales')->insertGetId([
            'codigo' => $payload['codigo'],
            'nombre' => $payload['nombre'],
            'direccion' => $payload['direccion'] ?? null,
            'telefono' => $payload['telefono'] ?? null,
            'ciudad' => $payload['ciudad'] ?? null,
            'encargado_id' => $payload['encargado_id'] ?? null,
            'activo' => $payload['activo'] ?? true,
            'created_at' => now(),
        ]);

        DB::table('audit_log')->insert([
            'usuario_id' => $uid,
            'accion' => 'sucursal_created',
            'entidad' => 'sucursales',
            'entidad_id' => (string) $id,
            'payload' => DB::raw("'{}'::jsonb"),
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        $fresh = DB::table('sucursales')->where('id', $id)->first();
        return response()->json(['ok' => true, 'data' => $fresh]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        $user = $uid > 0 ? DB::table('usuarios')->leftJoin('roles', 'roles.id', '=', 'usuarios.rol_id')->where('usuarios.id', $uid)->select(['roles.codigo as rol_codigo'])->first() : null;
        $isAdmin = $user && (string) ($user->rol_codigo ?? '') === 'administrador';
        if (!$isAdmin) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $row = DB::table('sucursales')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        $payload = $request->validate([
            'codigo' => ['nullable', 'string', 'max:20'],
            'nombre' => ['nullable', 'string', 'max:120'],
            'direccion' => ['nullable', 'string'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'ciudad' => ['nullable', 'string', 'max:80'],
            'encargado_id' => ['nullable', 'integer', 'min:1'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $update = [];
        foreach ($payload as $k => $v) {
            if ($v !== null) $update[$k] = $v;
        }
        if ($update) {
            DB::table('sucursales')->where('id', $id)->update($update);
        }

        DB::table('audit_log')->insert([
            'usuario_id' => $uid,
            'accion' => 'sucursal_updated',
            'entidad' => 'sucursales',
            'entidad_id' => (string) $id,
            'payload' => DB::raw("'{}'::jsonb"),
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        $fresh = DB::table('sucursales')->where('id', $id)->first();
        return response()->json(['ok' => true, 'data' => $fresh]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        $user = $uid > 0 ? DB::table('usuarios')->leftJoin('roles', 'roles.id', '=', 'usuarios.rol_id')->where('usuarios.id', $uid)->select(['roles.codigo as rol_codigo'])->first() : null;
        $isAdmin = $user && (string) ($user->rol_codigo ?? '') === 'administrador';
        if (!$isAdmin) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $row = DB::table('sucursales')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        DB::table('sucursales')->where('id', $id)->delete();

        DB::table('audit_log')->insert([
            'usuario_id' => $uid,
            'accion' => 'sucursal_deleted',
            'entidad' => 'sucursales',
            'entidad_id' => (string) $id,
            'payload' => DB::raw("'{}'::jsonb"),
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
