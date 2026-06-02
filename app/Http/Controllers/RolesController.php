<?php

namespace App\Http\Controllers;

use App\Infrastructure\Remote\RemoteDataClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class RolesController
{
    public function __construct(
        private readonly RemoteDataClient $data,
    ) {
    }

    public function index(): View
    {
        return view('roles.index');
    }

    public function data()
    {
        return response()->json(['ok' => true, 'data' => $this->data->roles()]);
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'codigo' => ['required', 'string', 'max:30'],
            'nombre' => ['required', 'string', 'max:80'],
            'protegido' => ['nullable', 'boolean'],
        ]);

        $res = $this->data->crearRol([
            'codigo' => (string) $payload['codigo'],
            'nombre' => (string) $payload['nombre'],
            'protegido' => (bool) ($payload['protegido'] ?? false),
        ]);

        if (isset($res['error'])) {
            return response()->json(['ok' => false, 'error' => (string) $res['error']], 422);
        }

        return response()->json(['ok' => true, 'data' => $res], 201);
    }

    public function update(Request $request, int $id)
    {
        $payload = $request->validate([
            'codigo' => ['required', 'string', 'max:30'],
            'nombre' => ['required', 'string', 'max:80'],
        ]);

        $res = $this->data->actualizarRol($id, [
            'codigo' => (string) $payload['codigo'],
            'nombre' => (string) $payload['nombre'],
        ]);

        if (isset($res['error'])) {
            return response()->json(['ok' => false, 'error' => (string) $res['error']], 422);
        }

        return response()->json(['ok' => true, 'data' => $res]);
    }

    public function destroy(int $id)
    {
        $ok = $this->data->eliminarRol($id);
        return $ok
            ? response()->json(['ok' => true])
            : response()->json(['ok' => false, 'error' => 'No se pudo eliminar el rol.'], 422);
    }
}

