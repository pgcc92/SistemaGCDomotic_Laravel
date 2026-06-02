<?php

namespace App\Http\Controllers;

use App\Infrastructure\Remote\RemoteDataClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ClientesController
{
    public function __construct(
        private readonly RemoteDataClient $data,
    ) {
    }

    public function index(): View
    {
        return view('clientes.index');
    }

    public function show(int $id): View|JsonResponse
    {
        $data = $this->data->cliente($id);

        if (request()->expectsJson()) {
            return response()->json([
                'ok' => !isset($data['error']),
                'data' => $data,
            ]);
        }

        return view('clientes.show', [
            'cliente' => $data['cliente'] ?? null,
            'tickets' => $data['tickets'] ?? [],
            'ventas' => $data['ventas'] ?? [],
            'dispositivos' => $data['dispositivos'] ?? [],
        ]);
    }

    public function create(): View
    {
        return view('clientes.form', ['cliente' => null]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $payload = $request->validate([
            'telefono' => ['required', 'string', 'max:50'],
            'nombre' => ['nullable', 'string', 'max:200'],
            'modelos_cerraduras' => ['nullable', 'string'],
            'direccion' => ['nullable', 'string'],
            'canal' => ['nullable', 'string', 'max:50'],
            'notas' => ['nullable', 'string'],
            'fecha_instalacion' => ['nullable', 'date'],
            'garantia_meses' => ['nullable', 'integer', 'min:0'],
            'tipo_cliente' => ['nullable', 'string', 'max:50'],
            'perfil_interaccion' => ['nullable', 'string'],
            'tipo_documento' => ['nullable', 'string', 'max:10'],
            'numero_documento' => ['nullable', 'string', 'max:20'],
            'razon_social' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'string', 'max:150'],
        ]);

        $res = $this->data->crearCliente($payload);
        if (isset($res['error'])) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'error' => (string) $res['error']], 422);
            }
            return back()->withErrors(['telefono' => (string) $res['error']])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'data' => $res]);
        }

        return redirect()->route('clientes.index')->with('status', 'Cliente creado.');
    }

    public function edit(int $id): View
    {
        $data = $this->data->cliente($id);
        return view('clientes.form', ['cliente' => $data['cliente'] ?? null]);
    }

    public function update(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $payload = $request->validate([
            'telefono' => ['required', 'string', 'max:50'],
            'nombre' => ['nullable', 'string', 'max:200'],
            'modelos_cerraduras' => ['nullable', 'string'],
            'direccion' => ['nullable', 'string'],
            'canal' => ['nullable', 'string', 'max:50'],
            'notas' => ['nullable', 'string'],
            'fecha_instalacion' => ['nullable', 'date'],
            'garantia_meses' => ['nullable', 'integer', 'min:0'],
            'tipo_cliente' => ['nullable', 'string', 'max:50'],
            'perfil_interaccion' => ['nullable', 'string'],
            'tipo_documento' => ['nullable', 'string', 'max:10'],
            'numero_documento' => ['nullable', 'string', 'max:20'],
            'razon_social' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'string', 'max:150'],
        ]);

        $res = $this->data->actualizarCliente($id, $payload);
        if (isset($res['error'])) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'error' => (string) $res['error']], 422);
            }
            return back()->withErrors(['telefono' => (string) $res['error']])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'data' => $res]);
        }

        return redirect()->route('clientes.show', ['id' => $id])->with('status', 'Cliente actualizado.');
    }

    public function destroy(int $id): RedirectResponse|JsonResponse
    {
        $ok = $this->data->eliminarCliente($id);
        if (request()->expectsJson()) {
            return $ok
                ? response()->json(['ok' => true])
                : response()->json(['ok' => false, 'error' => 'No se pudo eliminar.'], 422);
        }
        return redirect()->route('clientes.index')->with('status', $ok ? 'Cliente eliminado.' : 'No se pudo eliminar.');
    }
}
