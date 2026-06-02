<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Authz\SuperAdminService;
use App\Domain\Rbac\RbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class UsuariosController
{
    public function __construct(
        private readonly SuperAdminService $superAdmin,
        private readonly RbacService $rbac,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $limit = max(1, min(200, (int) $request->query('limit', 50)));
        $qText = trim((string) $request->query('q', ''));
        $dashboardAct = $request->query('dashboard_activo');
        $roleCodes = trim((string) $request->query('role_codes', ''));

        $q = DB::table('usuarios')
            ->leftJoin('roles', 'roles.id', '=', 'usuarios.rol_id');

        if ($dashboardAct !== null && $dashboardAct !== '') {
            $q->where('usuarios.dashboard_activo', filter_var($dashboardAct, FILTER_VALIDATE_BOOL));
        }

        if ($roleCodes !== '') {
            $codes = array_values(array_filter(array_map('trim', explode(',', $roleCodes))));
            if ($codes) {
                $q->whereIn('roles.codigo', $codes);
            }
        }

        if ($qText !== '') {
            $qq = mb_strtolower($qText);
            $q->where(function ($w) use ($qq) {
                $w->whereRaw('lower(usuarios.numero_documento) like ?', ["%{$qq}%"])
                    ->orWhereRaw('lower(usuarios.nombre) like ?', ["%{$qq}%"])
                    ->orWhereRaw('lower(usuarios.email) like ?', ["%{$qq}%"])
                    ->orWhereRaw('lower(usuarios.telefono) like ?', ["%{$qq}%"]);
            });
        }

        $rows = $q
            ->select([
                'usuarios.id',
                'usuarios.numero_documento',
                'usuarios.nombre',
                'usuarios.email',
                'usuarios.telefono',
                'usuarios.rol_id',
                'roles.codigo as rol_codigo',
                'usuarios.sucursal_id',
                'usuarios.tecnico_id',
                'usuarios.activo',
                'usuarios.dashboard_activo',
                'usuarios.twofa_enabled',
                'usuarios.created_at',
                'usuarios.updated_at',
            ])
            ->orderBy('usuarios.id', 'desc')
            ->limit($limit)
            ->get();
        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        if ($uid <= 0 || !$this->rbac->can($uid, 'usuarios', 'crear')) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $payload = $request->validate([
            'numero_documento' => ['required', 'string', 'max:20'],
            'nombre' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'rol_id' => ['nullable', 'integer', 'min:1'],
            'sucursal_id' => ['nullable', 'integer', 'min:1'],
            'tecnico_id' => ['nullable', 'integer', 'min:1'],
            'activo' => ['nullable', 'boolean'],
            'dashboard_activo' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:6', 'max:120'],
        ]);

        $passPlain = $payload['password'] ?? Str::random(12);
        $hash = password_hash($passPlain, PASSWORD_ARGON2ID);
        if (!$hash) {
            return response()->json(['ok' => false, 'error' => 'No se pudo generar password'], 500);
        }

        $id = (int) DB::table('usuarios')->insertGetId([
            'tipo_documento' => 'DNI',
            'numero_documento' => (string) $payload['numero_documento'],
            'nombre' => (string) $payload['nombre'],
            'email' => $payload['email'] ?? null,
            'telefono' => $payload['telefono'] ?? null,
            'password_hash' => $hash,
            'rol_id' => $payload['rol_id'] ?? null,
            'sucursal_id' => $payload['sucursal_id'] ?? null,
            'tecnico_id' => $payload['tecnico_id'] ?? null,
            'twofa_enabled' => false,
            'debe_cambiar_pass' => true,
            'activo' => $payload['activo'] ?? true,
            'dashboard_activo' => $payload['dashboard_activo'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('audit_log')->insert([
            'usuario_id' => $uid,
            'accion' => 'usuario_created',
            'entidad' => 'usuarios',
            'entidad_id' => (string) $id,
            'payload' => DB::raw("'{}'::jsonb"),
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        $fresh = DB::table('usuarios')
            ->select(['id','numero_documento','nombre','email','telefono','rol_id','sucursal_id','tecnico_id','activo','dashboard_activo','twofa_enabled','created_at','updated_at'])
            ->where('id', $id)->first();

        return response()->json(['ok' => true, 'data' => $fresh, 'password_plain' => $passPlain]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        if ($uid <= 0 || !$this->rbac->can($uid, 'usuarios', 'editar')) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $row = DB::table('usuarios')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        $payload = $request->validate([
            'numero_documento' => ['nullable', 'string', 'max:20'],
            'nombre' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'rol_id' => ['nullable', 'integer', 'min:1'],
            'sucursal_id' => ['nullable', 'integer', 'min:1'],
            'tecnico_id' => ['nullable', 'integer', 'min:1'],
            'activo' => ['nullable', 'boolean'],
            'dashboard_activo' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:6', 'max:120'],
        ]);

        $update = [];
        foreach (['numero_documento','nombre','email','telefono','rol_id','sucursal_id','tecnico_id','activo','dashboard_activo'] as $k) {
            if (array_key_exists($k, $payload) && $payload[$k] !== null) {
                $update[$k] = $payload[$k];
            }
        }
        if (!empty($payload['password'])) {
            $hash = password_hash((string) $payload['password'], PASSWORD_ARGON2ID);
            if (!$hash) {
                return response()->json(['ok' => false, 'error' => 'No se pudo hashear password'], 500);
            }
            $update['password_hash'] = $hash;
            $update['debe_cambiar_pass'] = true;
        }
        $update['updated_at'] = now();

        DB::table('usuarios')->where('id', $id)->update($update);

        DB::table('audit_log')->insert([
            'usuario_id' => $uid,
            'accion' => 'usuario_updated',
            'entidad' => 'usuarios',
            'entidad_id' => (string) $id,
            'payload' => DB::raw("'{}'::jsonb"),
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        $fresh = DB::table('usuarios')
            ->select(['id','numero_documento','nombre','email','telefono','rol_id','sucursal_id','tecnico_id','activo','dashboard_activo','twofa_enabled','created_at','updated_at'])
            ->where('id', $id)->first();

        return response()->json(['ok' => true, 'data' => $fresh]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        if ($uid <= 0 || !$this->rbac->can($uid, 'usuarios', 'eliminar')) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $row = DB::table('usuarios')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        DB::table('usuarios')->where('id', $id)->delete();

        DB::table('audit_log')->insert([
            'usuario_id' => $uid,
            'accion' => 'usuario_deleted',
            'entidad' => 'usuarios',
            'entidad_id' => (string) $id,
            'payload' => DB::raw("'{}'::jsonb"),
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function permisos(Request $request, int $id): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        $isAdminRole = $this->isAdmin($uid);
        // Lectura: permitido a administradores que tengan acceso al módulo usuarios.
        // Escritura se controla en permisosUpdate().
        if ($uid <= 0 || !$isAdminRole || !$this->rbac->can($uid, 'usuarios', 'editar')) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $rows = DB::table('usuario_permisos')->where('usuario_id', $id)->get();
        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function permisosUpdate(Request $request, int $id): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        $isAdminRole = $this->isAdmin($uid);
        if (!$this->superAdmin->canManagePermissions($uid, $isAdminRole)) {
            return response()->json(['ok' => false, 'error' => 'Solo el administrador principal puede editar permisos por usuario.'], 403);
        }

        // Reclamar ownership si aún no existe.
        $this->superAdmin->claimIfEmpty($uid, $isAdminRole);

        $payload = $request->validate([
            'changes' => ['required', 'array', 'min:1'],
            'changes.*.modulo_id' => ['required', 'integer', 'min:1'],
            'changes.*.accion_id' => ['required', 'integer', 'min:1'],
            'changes.*.permitido' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($payload, $id, $uid, $request) {
            foreach ($payload['changes'] as $c) {
                DB::table('usuario_permisos')->updateOrInsert(
                    [
                        'usuario_id' => $id,
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
                'accion' => 'usuario_permisos_updated',
                'entidad' => 'usuario_permisos',
                'entidad_id' => (string) $id,
                'payload' => DB::raw("'{}'::jsonb"),
                'ip' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return response()->json(['ok' => true]);
    }

    private function isAdmin(int $uid): bool
    {
        if ($uid <= 0) return false;
        $u = DB::table('usuarios')->leftJoin('roles', 'roles.id', '=', 'usuarios.rol_id')
            ->where('usuarios.id', $uid)->select(['roles.codigo as rol_codigo'])->first();
        return $u && (string) ($u->rol_codigo ?? '') === 'administrador';
    }
}
