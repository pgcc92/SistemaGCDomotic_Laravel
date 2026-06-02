<?php

namespace App\Http\Controllers;

use App\Infrastructure\Remote\RemoteDataClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class UsuariosController
{
    public function __construct(
        private readonly RemoteDataClient $data,
    ) {
    }

    public function index(): View
    {
        return view('usuarios.index');
    }

    public function data(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $this->data->usuariosAll(500),
            'meta' => [
                'roles' => $this->data->roles(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
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

        $res = $this->data->crearUsuario($payload);
        if (isset($res['error'])) {
            return response()->json(['ok' => false, 'error' => (string) $res['error']], 422);
        }
        return response()->json(['ok' => true, 'data' => $res]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
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

        $res = $this->data->actualizarUsuario($id, $payload);
        if (isset($res['error'])) {
            return response()->json(['ok' => false, 'error' => (string) $res['error']], 422);
        }
        return response()->json(['ok' => true, 'data' => $res]);
    }

    public function destroy(int $id): JsonResponse
    {
        $ok = $this->data->eliminarUsuario($id);
        return $ok ? response()->json(['ok' => true]) : response()->json(['ok' => false, 'error' => 'No se pudo eliminar.'], 422);
    }

    public function permisos(int $id): JsonResponse
    {
        return response()->json(['ok' => true, 'data' => $this->data->usuarioPermisos($id)]);
    }

    public function permisosUpdate(Request $request, int $id): JsonResponse
    {
        $payload = $request->validate([
            'changes' => ['required', 'array', 'min:1'],
            'changes.*.modulo_id' => ['required', 'integer', 'min:1'],
            'changes.*.accion_id' => ['required', 'integer', 'min:1'],
            'changes.*.permitido' => ['required', 'boolean'],
        ]);

        $ok = $this->data->usuarioPermisosUpdate($id, (array) $payload['changes']);
        return $ok ? response()->json(['ok' => true]) : response()->json(['ok' => false, 'error' => 'No se pudo guardar.'], 422);
    }
}
