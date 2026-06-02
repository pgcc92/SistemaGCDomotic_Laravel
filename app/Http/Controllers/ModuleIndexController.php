<?php

namespace App\Http\Controllers;

use App\Infrastructure\Remote\RemoteDataClient;
use Illuminate\View\View;

final class ModuleIndexController
{
    public function __construct(
        private readonly RemoteDataClient $data,
    ) {
    }

    public function __invoke(string $module = ''): View
    {
        if ($module === '') {
            $module = (string) request()?->route('module', 'modulo');
        }

        return view('modules.index', [
            'module' => $module,
            'rows' => $this->rowsFor($module),
        ]);
    }

    /** @return array<int,mixed> */
    private function rowsFor(string $module): array
    {
        return match ($module) {
            'clientes' => $this->data->clientes(25),
            'tickets' => $this->data->tickets(25),
            'ventas' => $this->data->ventas(25),
            'productos' => $this->data->stock(),
            'comisiones' => $this->data->comisiones(25),
            'sucursales' => $this->data->sucursales(25),
            'auditoria' => $this->data->auditoria(25),
            'usuarios' => $this->data->usuarios(25),
            'soporte_videos' => $this->data->soporteVideos(25),
            default => [],
        };
    }
}
