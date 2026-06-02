<?php

namespace App\Http\Controllers;

use App\Infrastructure\Remote\RemoteDataClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PermisosController
{
    public function __construct(
        private readonly RemoteDataClient $data,
    ) {
    }

    public function index(): View
    {
        $matrix = $this->data->permisosMatrix();

        return view('permisos.index', [
            'matrix' => $matrix,
            'error' => $matrix['error'] ?? null,
        ]);
    }

    public function matrixData(): JsonResponse
    {
        $matrix = $this->data->permisosMatrix();
        return isset($matrix['error'])
            ? response()->json(['ok' => false, 'error' => (string) $matrix['error']], 422)
            : response()->json(['ok' => true, 'data' => $matrix]);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $payload = $request->validate([
            'changes' => ['required', 'array', 'min:1'],
            'changes.*.rol_id' => ['required', 'integer', 'min:1'],
            'changes.*.modulo_id' => ['required', 'integer', 'min:1'],
            'changes.*.accion_id' => ['required', 'integer', 'min:1'],
            'changes.*.permitido' => ['required', 'boolean'],
        ]);

        $ok = $this->data->permisosUpdate((array) $payload['changes']);

        if ($request->expectsJson()) {
            return $ok
                ? response()->json(['ok' => true])
                : response()->json(['ok' => false, 'error' => 'No se pudo guardar.'], 422);
        }

        return redirect()->route('permisos.index')->with('status', $ok ? 'Permisos guardados.' : 'No se pudo guardar.');
    }
}
