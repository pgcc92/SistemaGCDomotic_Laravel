<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Authz\SuperAdminService;
use App\Domain\Rbac\RbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class PermisosMatrixController
{
    public function __construct(
        private readonly RbacService $rbac,
        private readonly SuperAdminService $superAdmin,
    ) {
    }

    private function ensureCatalog(): void
    {
        // IMPORTANTE: no dependemos de Cache::remember aquí.
        // En cPanel es común que el filesystem cache falle por permisos, y eso rompe /permisos/matrix con 500.
        // Estas operaciones son idempotentes y baratas (insertOrIgnore/upsert).

        // Acciones estándar (verdad técnica) + extensión:
        // - ver_general: permite "visibilidad general" (no solo asignados).
        $acciones = ['ver', 'ver_general', 'crear', 'editar', 'eliminar', 'exportar', 'asignar', 'aprobar'];
        $accionesRows = array_map(fn ($c) => ['codigo' => $c], $acciones);
        DB::table('acciones')->insertOrIgnore($accionesRows);

        // Módulos estándar usados por la UI (idempotente)
        $mods = [
            ['codigo' => 'dashboard', 'nombre' => 'Dashboard', 'orden' => 0, 'activo' => true],
            ['codigo' => 'clientes', 'nombre' => 'Clientes', 'orden' => 10, 'activo' => true],
            ['codigo' => 'tickets', 'nombre' => 'Tickets', 'orden' => 20, 'activo' => true],
            ['codigo' => 'agenda', 'nombre' => 'Agenda', 'orden' => 25, 'activo' => true],
            ['codigo' => 'productos', 'nombre' => 'Productos', 'orden' => 30, 'activo' => true],
            ['codigo' => 'ventas', 'nombre' => 'Ventas', 'orden' => 40, 'activo' => true],
            ['codigo' => 'comisiones', 'nombre' => 'Comisiones', 'orden' => 50, 'activo' => true],
            ['codigo' => 'dispositivos', 'nombre' => 'Instalaciones', 'orden' => 60, 'activo' => true],
            ['codigo' => 'soporte_videos', 'nombre' => 'Soporte videos', 'orden' => 70, 'activo' => true],
            ['codigo' => 'reportes', 'nombre' => 'Reportes', 'orden' => 80, 'activo' => true],
            ['codigo' => 'usuarios', 'nombre' => 'Usuarios', 'orden' => 90, 'activo' => true],
            ['codigo' => 'roles', 'nombre' => 'Roles', 'orden' => 95, 'activo' => true],
            ['codigo' => 'permisos', 'nombre' => 'Permisos', 'orden' => 96, 'activo' => true],
            ['codigo' => 'sucursales', 'nombre' => 'Sucursales', 'orden' => 97, 'activo' => true],
            ['codigo' => 'auditoria', 'nombre' => 'Auditoría', 'orden' => 98, 'activo' => true],
            ['codigo' => 'configuracion', 'nombre' => 'Configuración', 'orden' => 99, 'activo' => true],
        ];
        DB::table('modulos')->upsert($mods, ['codigo'], ['nombre', 'orden', 'activo']);

        // Roles base típicos (si no existen)
        $baseRoles = [
            ['codigo' => 'administrador', 'nombre' => 'Administrador', 'protegido' => true],
            ['codigo' => 'vendedor', 'nombre' => 'Vendedor', 'protegido' => false],
            ['codigo' => 'tecnico', 'nombre' => 'Técnico', 'protegido' => false],
            ['codigo' => 'instalador', 'nombre' => 'Instalador', 'protegido' => false],
        ];
        DB::table('roles')->upsert($baseRoles, ['codigo'], ['nombre', 'protegido']);
    }

    public function __invoke(Request $request): JsonResponse
    {
        $this->ensureCatalog();

        $roles = DB::table('roles')->orderBy('id')->get();
        $modulos = DB::table('modulos')->where('activo', true)->orderBy('orden')->orderBy('id')->get();
        $acciones = DB::table('acciones')->orderBy('id')->get();
        // Optimizamos payload: solo enviamos los permitidos=true. Lo no presente se asume false.
        $rolPerms = DB::table('rol_permisos')
            ->where('permitido', true)
            ->select(['rol_id', 'modulo_id', 'accion_id', 'permitido'])
            ->get();

        $uid = (int) $request->attributes->get('remote_uid', 0);
        $isAdminRole = $uid > 0 ? $this->rbac->isAdmin($uid) : false;
        $ownerId = $this->superAdmin->ownerId();
        $canManage = $uid > 0 ? $this->superAdmin->canManagePermissions($uid, $isAdminRole) : false;

        return response()->json([
            'ok' => true,
            'data' => [
                'roles' => $roles,
                'modulos' => $modulos,
                'acciones' => $acciones,
                'rol_permisos' => $rolPerms,
                'super_admin' => [
                    'enabled' => $this->superAdmin->isEnabled(),
                    'owner_id' => $ownerId,
                    'can_manage' => $canManage,
                ],
            ],
        ]);
    }
}
