<?php

namespace App\Http\Controllers;

use App\Infrastructure\Remote\RemoteDataClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class VentasController
{
    public function __construct(
        private readonly RemoteDataClient $data,
    ) {
    }

    public function index(): View
    {
        $stats = $this->data->ventasStats();
        return view('ventas.index', [
            'stats' => $stats,
        ]);
    }

    public function show(int $id): View|JsonResponse
    {
        $venta = $this->data->venta($id);

        if (request()->expectsJson()) {
            return response()->json([
                'ok' => !isset($venta['error']),
                'data' => $venta,
            ]);
        }

        return view('ventas.show', [
            'id' => $id,
            'venta' => $venta,
            'error' => $venta['error'] ?? null,
        ]);
    }

    public function create(): View
    {
        return view('ventas.create');
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'cliente_id' => ['nullable', 'integer', 'min:1'],
            'ticket_id' => ['nullable', 'string', 'max:50'],
            'sucursal_id' => ['nullable', 'integer', 'min:1'],
            'vendedor_id' => ['nullable', 'integer', 'min:1'],
            'tipo_documento' => ['required', 'string', 'max:20'],
            'serie_documento' => ['nullable', 'string', 'max:10'],
            'numero_documento' => ['nullable', 'string', 'max:20'],
            'moneda' => ['nullable', 'string', 'max:3'],
            'tipo_cambio' => ['nullable', 'numeric'],
            'metodo_pago' => ['nullable', 'string', 'max:40'],
            'pago_referencia' => ['nullable', 'string', 'max:100'],
            'notas' => ['nullable', 'string'],
            'estado' => ['nullable', 'string', 'max:30'],
            'items_json' => ['required', 'string'],
        ]);

        $items = json_decode((string) $payload['items_json'], true);
        if (!is_array($items)) {
            return back()->withErrors(['items_json' => 'items_json debe ser un JSON array.'])->withInput();
        }

        $apiPayload = [
            'cliente_id' => $payload['cliente_id'] ?? null,
            'ticket_id' => $payload['ticket_id'] ?? null,
            'sucursal_id' => $payload['sucursal_id'] ?? null,
            'vendedor_id' => $payload['vendedor_id'] ?? null,
            'tipo_documento' => $payload['tipo_documento'],
            'serie_documento' => $payload['serie_documento'] ?? null,
            'numero_documento' => $payload['numero_documento'] ?? null,
            'moneda' => $payload['moneda'] ?? 'PEN',
            'tipo_cambio' => $payload['tipo_cambio'] ?? null,
            'metodo_pago' => $payload['metodo_pago'] ?? null,
            'pago_referencia' => $payload['pago_referencia'] ?? null,
            'notas' => $payload['notas'] ?? null,
            'estado' => $payload['estado'] ?? null,
            'items' => $items,
        ];

        $res = $this->data->crearVenta($apiPayload);
        $ventaId = (int) ($res['id'] ?? ($res['venta']['id'] ?? 0));

        if ($ventaId <= 0) {
            $msg = (string) ($res['error'] ?? 'No se pudo crear la venta.');
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'error' => $msg], 422);
            }
            return back()->withErrors(['tipo_documento' => $msg])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'data' => $res]);
        }

        return redirect()->route('ventas.show', ['id' => $ventaId])->with('status', 'Venta creada.');
    }

    public function stats(Request $request): JsonResponse
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $data = $this->data->ventasStats($from ? (string) $from : null, $to ? (string) $to : null);
        return response()->json(['ok' => true, 'data' => $data]);
    }

    public function nextCorrelativo(Request $request): JsonResponse
    {
        $sucursalId = $request->query('sucursal_id');
        $tipo = $request->query('tipo_documento');
        $serie = $request->query('serie');

        $data = $this->data->ventasNextCorrelativo(
            $sucursalId !== null ? (int) $sucursalId : null,
            $tipo !== null ? (string) $tipo : null,
            $serie !== null ? (string) $serie : null,
        );

        return response()->json(['ok' => !isset($data['error']), 'data' => $data]);
    }

    public function pagar(Request $request, int $id)
    {
        $payload = $request->validate([
            'metodo' => ['nullable', 'string', 'max:40'],
            'referencia' => ['nullable', 'string', 'max:100'],
        ]);

        $res = $this->data->pagarVenta($id, [
            'metodo' => $payload['metodo'] ?? null,
            'referencia' => $payload['referencia'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return isset($res['error'])
                ? response()->json(['ok' => false, 'error' => (string) $res['error']], 422)
                : response()->json(['ok' => true, 'data' => $res]);
        }

        return isset($res['error'])
            ? back()->withErrors(['venta' => (string) $res['error']])
            : back()->with('status', 'Venta pagada.');
    }

    public function anular(Request $request, int $id)
    {
        $payload = $request->validate([
            'motivo' => ['nullable', 'string', 'max:150'],
        ]);

        $res = $this->data->anularVenta($id, [
            'motivo' => $payload['motivo'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return isset($res['error'])
                ? response()->json(['ok' => false, 'error' => (string) $res['error']], 422)
                : response()->json(['ok' => true, 'data' => $res]);
        }

        return isset($res['error'])
            ? back()->withErrors(['venta' => (string) $res['error']])
            : back()->with('status', 'Venta anulada.');
    }
}
