<?php

namespace App\Domain\Authz;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Super admin" por ownership (sin cambiar roles en BD).
 *
 * - Todos los usuarios con rol 'administrador' existen, pero:
 * - Si la tabla app_super_admin está vacía, el primer admin que intente guardar permisos
 *   "reclama" el ownership.
 * - Desde ese momento, solo ese usuario puede gestionar permisos (rol_permisos / usuario_permisos).
 *
 * Nota: si la tabla NO existe, se comporta de forma backward-compatible (cualquier admin puede).
 */
final class SuperAdminService
{
    public function isEnabled(): bool
    {
        return Schema::hasTable('app_super_admin');
    }

    public function ownerId(): ?int
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $id = DB::table('app_super_admin')->orderBy('created_at', 'asc')->value('usuario_id');
        $id = $id ? (int) $id : null;
        return $id && $id > 0 ? $id : null;
    }

    public function isSuperAdmin(int $usuarioId, bool $isAdminRole): bool
    {
        if ($usuarioId <= 0 || !$isAdminRole) {
            return false;
        }

        // Si no está instalado, no bloqueamos (compat).
        if (!$this->isEnabled()) {
            return true;
        }

        $owner = $this->ownerId();
        // Mientras no exista owner, todos los admins actúan como "super" (hasta que el primero reclame).
        if (!$owner) {
            return true;
        }

        return $owner === $usuarioId;
    }

    public function canManagePermissions(int $usuarioId, bool $isAdminRole): bool
    {
        if ($usuarioId <= 0 || !$isAdminRole) {
            return false;
        }

        // Si no está instalado, se mantiene comportamiento anterior (admin puede).
        if (!$this->isEnabled()) {
            return true;
        }

        $owner = $this->ownerId();
        // Si aún no hay owner, el primer admin que guarde reclamará ownership.
        if (!$owner) {
            return true;
        }

        return $owner === $usuarioId;
    }

    /**
     * Reclama ownership si la tabla existe y está vacía.
     * Retorna el owner actual (después de intentar reclamar).
     */
    public function claimIfEmpty(int $usuarioId, bool $isAdminRole): ?int
    {
        if ($usuarioId <= 0 || !$isAdminRole || !$this->isEnabled()) {
            return $this->ownerId();
        }

        return DB::transaction(function () use ($usuarioId) {
            $existing = DB::table('app_super_admin')->lockForUpdate()->value('usuario_id');
            if ($existing) {
                return (int) $existing;
            }

            DB::table('app_super_admin')->insert([
                'usuario_id' => $usuarioId,
                'created_at' => now(),
            ]);

            return $usuarioId;
        });
    }
}

