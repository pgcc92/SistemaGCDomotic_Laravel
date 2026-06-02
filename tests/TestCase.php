<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Infrastructure\Remote\RemoteAuthClient;
use App\Infrastructure\Remote\RemoteRbacClient;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Evita dependencias de red en tests: simula login remoto.
        $this->app->bind(RemoteAuthClient::class, function () {
            return new class {
                /** @return array{token:string,usuario:array<string,mixed>} */
                public function login(string $documento, string $password): array
                {
                    if ($password !== 'password') {
                        throw new \RuntimeException('Credenciales inválidas');
                    }

                    return [
                        'token' => 'u1.fake',
                        'usuario' => [
                            'id' => 999,
                            'numero_documento' => $documento,
                            'nombre' => 'Remote User',
                            'email' => 'remote@example.com',
                            'rol_id' => null,
                            'sucursal_id' => null,
                            'tecnico_id' => null,
                            'twofa_enabled' => false,
                        ],
                    ];
                }
            };
        });

        $this->app->bind(RemoteRbacClient::class, function () {
            return new class {
                /** @return array<string,array<string,bool>> */
                public function myPermissions(): array
                {
                    return ['*' => ['*' => true]];
                }
            };
        });
    }
}
