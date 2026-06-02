<?php

namespace App\Http\Controllers;

use App\Infrastructure\Remote\RemoteDataClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class ComisionesController
{
    public function __construct(
        private readonly RemoteDataClient $data,
    ) {
    }

    public function index(): View
    {
        return view('comisiones.index');
    }

    public function data(): JsonResponse
    {
        $periodo = request()->query('periodo');
        $vendedorId = request()->query('vendedor_id');
        return response()->json([
            'ok' => true,
            'data' => $this->data->comisiones(
                500,
                is_string($periodo) ? $periodo : null,
                is_numeric($vendedorId) ? (int) $vendedorId : null,
            ),
        ]);
    }

    public function ventasPeriodo(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'periodo' => ['required', 'string'],
            'porcentaje' => ['nullable', 'numeric'],
            'instalador_fee' => ['nullable', 'numeric', 'min:0'],
            'venta_ids' => ['nullable', 'string'],
            'vendedor_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $res = $this->data->comisionesVentasPeriodo(
            (string) $payload['periodo'],
            array_key_exists('porcentaje', $payload) ? (float) ($payload['porcentaje'] ?? 0) : null,
            isset($payload['vendedor_id']) ? (int) $payload['vendedor_id'] : null,
            array_key_exists('instalador_fee', $payload) ? (float) ($payload['instalador_fee'] ?? 0) : null,
            array_key_exists('venta_ids', $payload) ? (string) ($payload['venta_ids'] ?? '') : null,
        );

        if (isset($res['error'])) {
            return response()->json(['ok' => false, 'error' => (string) $res['error']], 422);
        }

        return response()->json(['ok' => true, 'data' => $res]);
    }

    public function aprobar(int $id): JsonResponse
    {
        $ok = $this->data->comisionAprobar($id);
        return $ok
            ? response()->json(['ok' => true])
            : response()->json(['ok' => false, 'error' => 'No se pudo aprobar.'], 422);
    }

    public function aprobarBulk(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'periodo' => ['required', 'string'],
            'vendedor_id' => ['nullable', 'integer', 'min:1'],
            'venta_ids' => ['nullable', 'string'],
        ]);

        $res = $this->data->comisionesAprobarBulk(
            (string) $payload['periodo'],
            isset($payload['vendedor_id']) ? (int) $payload['vendedor_id'] : null,
            isset($payload['venta_ids']) ? (string) $payload['venta_ids'] : null,
        );

        if (isset($res['error'])) {
            return response()->json(['ok' => false, 'error' => (string) $res['error']], 422);
        }

        return response()->json(['ok' => true, 'data' => $res]);
    }

    public function liquidar(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'periodo' => ['required', 'string'],
            'vendedor_id' => ['nullable', 'integer', 'min:1'],
            'porcentaje' => ['nullable', 'numeric', 'min:0'],
            'instalador_fee' => ['nullable', 'numeric', 'min:0'],
            'referencia' => ['nullable', 'string', 'max:100'],
            'venta_ids' => ['required', 'string'],
            'fees' => ['nullable', 'array'],
        ]);

        $res = $this->data->comisionesLiquidar(
            (string) $payload['periodo'],
            isset($payload['vendedor_id']) ? (int) $payload['vendedor_id'] : null,
            array_key_exists('porcentaje', $payload) ? (float) ($payload['porcentaje'] ?? 0) : null,
            array_key_exists('instalador_fee', $payload) ? (float) ($payload['instalador_fee'] ?? 0) : null,
            isset($payload['referencia']) ? (string) $payload['referencia'] : null,
            (string) $payload['venta_ids'],
            isset($payload['fees']) && is_array($payload['fees']) ? $payload['fees'] : null,
        );

        if (isset($res['error'])) {
            return response()->json(['ok' => false, 'error' => (string) $res['error']], 422);
        }

        return response()->json(['ok' => true, 'data' => $res]);
    }

    public function pagar(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'periodo' => ['required', 'string'],
            'vendedor_id' => ['nullable', 'integer', 'min:1'],
            'referencia' => ['nullable', 'string', 'max:100'],
            'venta_ids' => ['nullable', 'string'],
        ]);

        $res = $this->data->comisionesPagar(
            (string) $payload['periodo'],
            isset($payload['vendedor_id']) ? (int) $payload['vendedor_id'] : null,
            isset($payload['referencia']) ? (string) $payload['referencia'] : null,
            isset($payload['venta_ids']) ? (string) $payload['venta_ids'] : null,
        );

        if (isset($res['error'])) {
            return response()->json(['ok' => false, 'error' => (string) $res['error']], 422);
        }

        return response()->json(['ok' => true, 'data' => $res]);
    }

    public function export(Request $request): Response
    {
        $periodo = $request->query('periodo');
        $res = $this->data->comisionesExportCsv(is_string($periodo) ? $periodo : null);
        if (isset($res['error'])) {
            return response((string) $res['error'], 422);
        }

        return response((string) ($res['csv'] ?? ''), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="comisiones.csv"',
        ]);
    }
}
