<?php

namespace App\Infrastructure\Remote;

use App\Infrastructure\Http\RemoteApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

final class RemoteAuthClient
{
    public function __construct(
        private readonly RemoteApiClient $api,
    ) {
    }

    /** @return array{token:string,usuario:array<string,mixed>} */
    public function login(string $documento, string $password): array
    {
        try {
            $res = $this->api->request()->post('/api/v1/auth/login', [
                'documento' => $documento,
                'password' => $password,
            ]);
        } catch (ConnectionException $e) {
            Log::warning('Remote auth connection failed', [
                'base_url' => config('gc.remote_api.base_url'),
                'message' => $e->getMessage(),
            ]);
            throw new \RuntimeException('No se pudo conectar al servicio de autenticación.');
        }

        if (!$res->ok()) {
            $status = $res->status();
            $msg = $res->json('error');

            if (!is_string($msg) || $msg === '') {
                // Fallback: cuerpo en texto (por ejemplo 429/HTML) para debug.
                $body = trim((string) $res->body());
                $body = mb_substr($body, 0, 200);
                Log::warning('Remote auth failed (non-json)', [
                    'status' => $status,
                    'base_url' => config('gc.remote_api.base_url'),
                    'body' => $body,
                ]);

                $msg = match ($status) {
                    401 => 'API key inválida o no configurada.',
                    429 => 'Demasiados intentos. Espera 60 segundos e intenta de nuevo.',
                    default => 'No se pudo iniciar sesión.',
                };
            }

            throw new \RuntimeException($msg);
        }

        $token = (string) $res->json('data.token');
        $usuario = $res->json('data.usuario');
        if ($token === '' || !is_array($usuario)) {
            Log::warning('Remote auth returned invalid payload', [
                'base_url' => config('gc.remote_api.base_url'),
                'payload' => $res->json(),
            ]);
            throw new \RuntimeException('Respuesta inválida del servicio de autenticación.');
        }

        return [
            'token' => $token,
            'usuario' => $usuario,
        ];
    }
}
