<?php

namespace App\Domain\Rbac;

use App\Domain\Authz\SuperAdminService;
use Illuminate\Support\Facades\DB;

final class RbacService
{
    /**
     * Cache en memoria por-request.
     *
     * Importante: NO usar cache estático aquí.
     * En PHP-FPM los "static" pueden persistir en el worker entre requests y
     * provocar permisos/roles "pegados" después de actualizar usuario_permisos/rol_permisos.
     *
     * @var array<int,bool>
     */
    private array $isAdminCache = [];

    /** @var array<int,array<string,array<string,bool>>> */
    private array $permsCache = [];

    public function __construct(
        private readonly SuperAdminService $superAdmin,
    ) {
    }

    public function isAdmin(int $usuarioId): bool
    {
        if ($usuarioId <= 0) {
            return false;
        }
        if (array_key_exists($usuarioId, $this->isAdminCache)) {
            return $this->isAdminCache[$usuarioId];
        }

        $rol = DB::table('usuarios')
            ->leftJoin('roles', 'roles.id', '=', 'usuarios.rol_id')
            ->where('usuarios.id', $usuarioId)
            ->value('roles.codigo');

        $isAdmin = (string) ($rol ?? '') === 'administrador';
        $this->isAdminCache[$usuarioId] = $isAdmin;
        return $isAdmin;
    }

    /** @return array<string,array<string,bool>> */
    public function permissionsForUser(int $usuarioId): array
    {
        if ($usuarioId <= 0) {
            return [];
        }
        if (array_key_exists($usuarioId, $this->permsCache)) {
            return $this->permsCache[$usuarioId];
        }

        $u = DB::table('usuarios')->where('id', $usuarioId)->first();
        if (!$u) {
            return $this->permsCache[$usuarioId] = [];
        }

        $rolId = $u->rol_id ? (int) $u->rol_id : null;

        $isAdminRole = $this->isAdmin($usuarioId);

        // Regla negocio: "Super admin" (owner) tiene acceso total.
        // Los demás administradores obedecen la jerarquía usuario_permisos > rol_permisos.
        if ($this->superAdmin->isSuperAdmin($usuarioId, $isAdminRole)) {
            return $this->permsCache[$usuarioId] = ['*' => ['*' => true]];
        }

        // Jerarquía: permisos por usuario > permisos por rol.
        // Si existen permisos por usuario, se aplican (deny-by-default).
        $hasUserPerms = DB::table('usuario_permisos')->where('usuario_id', $usuarioId)->exists();

        $rows = $hasUserPerms
            ? DB::table('usuario_permisos as up')
                ->join('modulos as m', 'm.id', '=', 'up.modulo_id')
                ->join('acciones as a', 'a.id', '=', 'up.accion_id')
                ->where('up.usuario_id', $usuarioId)
                ->select(['m.codigo as modulo', 'a.codigo as accion', 'up.permitido'])
                ->get()
            : ($rolId
                ? DB::table('rol_permisos as rp')
                    ->join('modulos as m', 'm.id', '=', 'rp.modulo_id')
                    ->join('acciones as a', 'a.id', '=', 'rp.accion_id')
                    ->where('rp.rol_id', $rolId)
                    ->select(['m.codigo as modulo', 'a.codigo as accion', 'rp.permitido'])
                    ->get()
                : collect());

        $perms = [];
        foreach ($rows as $r) {
            $m = (string) $r->modulo;
            $a = (string) $r->accion;
            $perms[$m] ??= [];
            $perms[$m][$a] = (bool) $r->permitido;
        }

        return $this->permsCache[$usuarioId] = $perms;
    }

    public function can(int $usuarioId, string $modulo, string $accion): bool
    {
        $perms = $this->permissionsForUser($usuarioId);
        if (isset($perms['*']['*']) && $perms['*']['*'] === true) {
            return true;
        }

        return (bool) ($perms[$modulo][$accion] ?? false);
    }

    public function canViewAll(int $usuarioId, string $modulo): bool
    {
        // Admin: ve todo.
        if ($this->isAdmin($usuarioId)) {
            return true;
        }

        // Permiso especial para visibilidad general (supervisor / encargado).
        if ($this->can($usuarioId, $modulo, 'ver_general')) {
            return true;
        }

        // Compatibilidad: si se activó "ver" pero aún no existe fila/acción "ver_general"
        // (por ejemplo, permisos históricos creados antes de que se agregue la acción),
        // no elevamos visibilidad por defecto. Sin embargo, si el usuario tiene permisos
        // por usuario (deny-by-default) y se le concedió explícitamente TODO el módulo,
        // permitimos visibilidad general.
        $perms = $this->permissionsForUser($usuarioId);
        $modulePerms = $perms[$modulo] ?? [];
        if (!$modulePerms) {
            return false;
        }

        // "Todo el módulo" = todas las acciones presentes para el módulo están permitidas.
        // Nota: el catálogo de acciones es global; si en el futuro agregamos más acciones,
        // esto seguirá siendo conservador (si falta alguna, no eleva).
        $acciones = DB::table('acciones')->pluck('codigo')->all();
        foreach ($acciones as $accion) {
            $a = (string) $accion;
            if ($a === 'ver_general') {
                continue;
            }
            if (($modulePerms[$a] ?? false) !== true) {
                return false;
            }
        }

        return true;
    }
}
