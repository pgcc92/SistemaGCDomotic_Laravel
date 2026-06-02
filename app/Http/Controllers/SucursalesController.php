<?php

namespace App\Http\Controllers;

use App\Infrastructure\Remote\RemoteDataClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SucursalesController
{
    public function __construct(
        private readonly RemoteDataClient $data,
    ) {
    }

    public function index(): View
    {
        return view('sucursales.index');
    }

    public function data(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $this->data->sucursalesAll(500),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'codigo' => ['required', 'string', 'max:20'],
            'nombre' => ['required', 'string', 'max:120'],
            'direccion' => ['nullable', 'string'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'ciudad' => ['nullable', 'string', 'max:80'],
            'encargado_id' => ['nullable', 'integer', 'min:1'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $res = $this->data->crearSucursal($payload);
        if (isset($res['error'])) {
            return response()->json(['ok' => false, 'error' => (string) $res['error']], 422);
        }
        return response()->json(['ok' => true, 'data' => $res]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $payload = $request->validate([
            'codigo' => ['nullable', 'string', 'max:20'],
            'nombre' => ['nullable', 'string', 'max:120'],
            'direccion' => ['nullable', 'string'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'ciudad' => ['nullable', 'string', 'max:80'],
            'encargado_id' => ['nullable', 'integer', 'min:1'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $res = $this->data->actualizarSucursal($id, $payload);
        if (isset($res['error'])) {
            return response()->json(['ok' => false, 'error' => (string) $res['error']], 422);
        }
        return response()->json(['ok' => true, 'data' => $res]);
    }

    public function destroy(int $id): JsonResponse
    {
        $ok = $this->data->eliminarSucursal($id);
        return $ok ? response()->json(['ok' => true]) : response()->json(['ok' => false, 'error' => 'No se pudo eliminar.'], 422);
    }
}

