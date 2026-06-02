<?php

namespace App\Domain\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class RemoteAuthService
{
    /** @return array{user:object} */
    public function attemptLogin(string $documento, string $password): array
    {
        $usuarios = 'usuarios';
        $intentos = 'intentos_login';
        $audit = 'audit_log';

        $doc = Str::upper($documento);
        $doc = preg_replace('/[^A-Z0-9]/', '', $doc) ?: '';

        if ($doc === '' || $password === '') {
            throw new \RuntimeException('Credenciales inválidas');
        }

        $user = DB::table($usuarios)->where('numero_documento', $doc)->first();
        if (!$user) {
            $this->logIntent($intentos, $doc, false, 'not_found');
            throw new \RuntimeException('Credenciales inválidas');
        }

        // Regla de acceso:
        // - Si existe la columna `dashboard_activo`, esa es la fuente de verdad para permitir dashboard.
        // - Si no existe, se usa `activo` como fallback.
        if (Schema::hasColumn($usuarios, 'dashboard_activo')) {
            if (!(bool) ($user->dashboard_activo ?? false)) {
                $this->logIntent($intentos, $doc, false, 'dashboard_inactivo');
                throw new \RuntimeException('Usuario inactivo');
            }
        } else {
            if (!(bool) ($user->activo ?? false)) {
                $this->logIntent($intentos, $doc, false, 'inactivo');
                throw new \RuntimeException('Usuario inactivo');
            }
        }

        if (!empty($user->bloqueado_hasta) && now()->lt($user->bloqueado_hasta)) {
            $this->logIntent($intentos, $doc, false, 'bloqueado');
            throw new \RuntimeException('Usuario bloqueado temporalmente');
        }

        $hash = (string) ($user->password_hash ?? '');
        if ($hash === '' || $hash === '!') {
            $this->logIntent($intentos, $doc, false, 'reset_required');
            throw new \RuntimeException('Requiere restablecer contraseña');
        }

        // `password_hash` viene del sistema existente. Soportamos hashes PHP (bcrypt/argon2id).
        if (!password_verify($password, $hash)) {
            DB::table($usuarios)->where('id', $user->id)->update([
                'intentos_fallidos' => ((int) $user->intentos_fallidos) + 1,
                'updated_at' => now(),
            ]);

            $this->logIntent($intentos, $doc, false, 'bad_password');
            throw new \RuntimeException('Credenciales inválidas');
        }

        DB::table($usuarios)->where('id', $user->id)->update([
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'ultimo_login' => now(),
            'updated_at' => now(),
        ]);

        $this->logIntent($intentos, $doc, true, null);
        DB::table($audit)->insert([
            'usuario_id' => $user->id,
            'accion' => 'login',
            'entidad' => 'usuarios',
            'entidad_id' => (string) $user->id,
            'payload' => DB::raw("'{}'::jsonb"),
            'ip' => request()?->ip(),
            'created_at' => now(),
        ]);

        return ['user' => $user];
    }

    private function logIntent(string $table, string $identificador, bool $ok, ?string $motivo): void
    {
        DB::table($table)->insert([
            'identificador' => $identificador,
            'ip' => request()?->ip() ?? '0.0.0.0',
            'user_agent' => (string) request()?->userAgent(),
            'exitoso' => $ok,
            'motivo' => $motivo,
            'created_at' => now(),
        ]);
    }
}
