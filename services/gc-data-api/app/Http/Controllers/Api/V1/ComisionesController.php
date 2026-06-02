<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Rbac\RbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ComisionesController
{
    public function __construct(
        private readonly RbacService $rbac,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        $canViewAll = $uid > 0 ? $this->rbac->canViewAll($uid, 'comisiones') : false;
        $isAdmin = $uid > 0 ? $this->rbac->isAdmin($uid) : false;

        $limit = max(1, min(200, (int) $request->query('limit', 50)));
        $periodo = $request->query('periodo');
        $vendedorId = $request->query('vendedor_id');
        $qText = trim((string) $request->query('q', ''));
        $q = DB::table('comisiones as c')
            ->leftJoin('ventas as v', 'v.id', '=', 'c.venta_id')
            ->leftJoin('usuarios as u', 'u.id', '=', 'c.vendedor_id')
            ->orderBy('c.id', 'desc');
        if (!$canViewAll && $uid > 0) {
            $q->where('c.vendedor_id', $uid);
        }
        if (is_string($periodo) && $periodo !== '') {
            $q->whereRaw("coalesce(to_char(v.fecha_venta::date, 'YYYY-MM'), c.periodo) = ?", [$periodo]);
        }
        if (($isAdmin || $canViewAll) && $vendedorId) {
            $q->where('c.vendedor_id', (int) $vendedorId);
        }
        if ($qText !== '') {
            $qq = mb_strtolower($qText);
            $q->where(function ($w) use ($qq) {
                $w->whereRaw('lower(v.venta_codigo) like ?', ["%{$qq}%"])
                    ->orWhereRaw('lower(u.nombre) like ?', ["%{$qq}%"])
                    ->orWhereRaw('lower(c.estado) like ?', ["%{$qq}%"])
                    ->orWhereRaw("lower(coalesce(to_char(v.fecha_venta::date, 'YYYY-MM'), c.periodo)) like ?", ["%{$qq}%"]);
            });
        }
        $rows = $q->limit($limit)->get([
            'c.id',
            'c.venta_id',
            'c.vendedor_id',
            'c.tipo_documento',
            'c.base_calculo',
            'c.porcentaje',
            'c.monto_comision',
            'c.moneda',
            'c.monto_pen',
            'c.estado',
            DB::raw("coalesce(to_char(v.fecha_venta::date, 'YYYY-MM'), c.periodo) as periodo"),
            'c.pago_referencia',
            'c.pagado_at',
            'c.created_at',
            'c.updated_at',
            'v.venta_codigo',
            'v.fecha_venta',
            'v.igv_porcentaje',
            'v.total',
            'v.total_pen',
            'u.nombre as vendedor_nombre',
        ]);
        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function ventasPeriodo(Request $request): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        $canViewAll = $uid > 0 ? $this->rbac->canViewAll($uid, 'comisiones') : false;
        $isAdmin = $uid > 0 ? $this->rbac->isAdmin($uid) : false;

        $periodo = (string) $request->query('periodo', '');
        $porcentaje = (float) $request->query('porcentaje', 0);
        $instaladorFee = (float) $request->query('instalador_fee', 0);
        $ventaIdsParam = $request->query('venta_ids');
        $vendedorId = $request->query('vendedor_id');
        $includePaid = (int) $request->query('include_paid', 0) === 1;

        if (!preg_match('/^\\d{4}-\\d{2}$/', $periodo)) {
            return response()->json(['ok' => false, 'error' => 'Periodo inválido (YYYY-MM)'], 422);
        }
        if ($instaladorFee < 0) {
            return response()->json(['ok' => false, 'error' => 'Monto instalador inválido'], 422);
        }

        $ventaIds = $this->parseVentaIds($ventaIdsParam);
        $ventaIdSet = $ventaIds ? array_fill_keys($ventaIds, true) : [];

        $from = "{$periodo}-01";
        $to = date('Y-m-d', strtotime("{$from} +1 month"));

        $q = DB::table('ventas')
            ->leftJoin('comisiones as c', 'c.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'PAGADA')
            ->where('ventas.fecha_venta', '>=', $from)
            ->where('ventas.fecha_venta', '<', $to);

        if (!$canViewAll) {
            $q->where('ventas.vendedor_id', $uid);
        } elseif (($isAdmin || $canViewAll) && $vendedorId) {
            $q->where('ventas.vendedor_id', (int) $vendedorId);
        }

        if (!$includePaid) {
            // Regla: si una venta ya tiene comisión PAGADA con monto > 0, no se considera "disponible" para calcular/pagar de nuevo.
            // Si está PAGADA pero monto es null/0 (deuda técnica), se permite recalcular/corregir.
            $q->where(function ($w) {
                $w->whereNull('c.id')
                    ->orWhere('c.estado', '!=', 'PAGADA')
                    ->orWhereRaw('coalesce(c.monto_pen, 0) <= 0');
            });
        }

        $rows = $q->select([
            'ventas.id as id',
            'ventas.venta_codigo',
            'ventas.vendedor_id',
            'ventas.tipo_documento',
            'ventas.igv_porcentaje',
            'ventas.moneda',
            'ventas.total',
            'ventas.total_pen',
            'ventas.fecha_venta',
        ])->orderBy('ventas.id', 'desc')->limit(500)->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'ok' => false,
                'error' => 'No hay ventas disponibles para comisionar en el periodo (ya están pagadas o no existen ventas PAGADAS).',
            ], 422);
        }

        $ventas = [];
        $basePen = 0.0;
        $igvPen = 0.0;
        $saldoPen = 0.0;
        $instaladorFeeTotal = 0.0;
        $porcentajeUsado = $porcentaje > 0 ? $porcentaje : null;

        foreach ($rows as $r) {
            $totalPen = (float) ($r->total_pen ?? $r->total ?? 0);
            $igvPct = (float) ($r->igv_porcentaje ?? 18);
            $tipoDoc = (string) ($r->tipo_documento ?? 'NOTA_VENTA');

            $include = $ventaIdSet ? (bool) ($ventaIdSet[(int) $r->id] ?? false) : true;
            $feeApplied = $include ? $instaladorFee : 0.0;
            $saldo = $include ? max(0.0, $totalPen - $feeApplied) : 0.0;

            $base = $saldo;
            $igv = 0.0;

            $isFacturaBoleta = in_array($tipoDoc, ['FACTURA', 'BOLETA'], true);
            if ($isFacturaBoleta) {
                $div = 1 + max(0.0, $igvPct) / 100;
                $base = $div > 0 ? ($saldo / $div) : $saldo;
                $igv = max(0.0, $saldo - $base);
            }

            $base = round($base, 2);
            $igv = round($igv, 2);
            $saldo = round($saldo, 2);

            // Porcentaje: si no viene explícito, buscar regla vigente por vendedor para la fecha de la venta.
            $pct = $porcentaje > 0 ? $porcentaje : $this->resolvePorcentaje((int) $r->vendedor_id, (string) $r->fecha_venta);
            $porcentajeUsado ??= $pct > 0 ? $pct : null;

            $monto = ($include && $pct > 0) ? round($base * ($pct / 100), 2) : 0.0;

            if ($include) {
                $saldoPen += $saldo;
                $basePen += $base;
                $igvPen += $igv;
                $instaladorFeeTotal += $feeApplied;
            }

            $ventas[] = [
                'id' => (int) $r->id,
                'venta_codigo' => (string) $r->venta_codigo,
                'vendedor_id' => (int) $r->vendedor_id,
                'tipo_documento' => $tipoDoc,
                'igv_porcentaje' => $igvPct,
                'moneda' => (string) $r->moneda,
                'total_pen' => round($totalPen, 2),
                'instalador_fee_pen' => round($feeApplied, 2),
                'saldo_pen' => $saldo,
                'base_calculo_pen' => $base,
                'igv_pen' => $igv,
                'include' => $include,
                'porcentaje' => $pct,
                'monto_comision_pen' => $monto,
                'fecha_venta' => (string) $r->fecha_venta,
            ];
        }

        $pctSummary = $porcentajeUsado ?? 0.0;
        $montoTotal = $pctSummary > 0 ? round($basePen * ($pctSummary / 100), 2) : round(array_sum(array_map(fn ($v) => (float) $v['monto_comision_pen'], $ventas)), 2);

        return response()->json([
            'ok' => true,
            'data' => [
                'periodo' => $periodo,
                'porcentaje' => $pctSummary,
                'instalador_fee_pen' => round($instaladorFee, 2),
                'instalador_fee_total_pen' => round($instaladorFeeTotal, 2),
                'venta_ids' => $ventaIds,
                'saldo_pen' => round($saldoPen, 2),
                'base_pen' => round($basePen, 2),
                'igv_pen' => round($igvPen, 2),
                'monto_comision_pen' => $montoTotal,
                'ventas' => $ventas,
            ],
        ]);
    }

    private function resolvePorcentaje(int $vendedorId, string $fechaVenta): float
    {
        if ($vendedorId <= 0) {
            return 0.0;
        }

        $date = substr($fechaVenta, 0, 10);
        if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date)) {
            $date = now()->toDateString();
        }

        $rule = DB::table('comision_reglas')
            ->where('activo', true)
            ->where('vigente_desde', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $date);
            })
            ->where(function ($q) use ($vendedorId) {
                $q->whereNull('vendedor_id')->orWhere('vendedor_id', $vendedorId);
            })
            ->orderByRaw('case when vendedor_id is null then 1 else 0 end')
            ->orderBy('id', 'desc')
            ->first();

        return $rule ? (float) ($rule->porcentaje ?? 0) : 0.0;
    }

    /** @return array<int,int> */
    private function parseVentaIds(mixed $ventaIdsParam): array
    {
        if ($ventaIdsParam === null || $ventaIdsParam === '') {
            return [];
        }

        $ids = [];
        if (is_array($ventaIdsParam)) {
            $ids = $ventaIdsParam;
        } elseif (is_string($ventaIdsParam)) {
            $ids = preg_split('/\\s*,\\s*/', trim($ventaIdsParam)) ?: [];
        } else {
            $ids = [(string) $ventaIdsParam];
        }

        $out = [];
        foreach ($ids as $v) {
            $n = (int) $v;
            if ($n > 0) {
                $out[$n] = $n;
            }
        }
        return array_values($out);
    }

    public function aprobar(Request $request, int $id): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        $isAdmin = $uid > 0 ? $this->rbac->isAdmin($uid) : false;
        if (!$isAdmin) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $row = DB::table('comisiones')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }
        if ((string) $row->estado !== 'PENDIENTE') {
            return response()->json(['ok' => false, 'error' => 'Estado inválido'], 422);
        }

        DB::table('comisiones')->where('id', $id)->update([
            'estado' => 'APROBADA',
            'aprobado_por' => $uid,
            'aprobado_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('audit_log')->insert([
            'usuario_id' => $uid,
            'accion' => 'comision_aprobada',
            'entidad' => 'comisiones',
            'entidad_id' => (string) $id,
            'payload' => DB::raw("'{}'::jsonb"),
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function aprobarBulk(Request $request): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        $isAdmin = $uid > 0 ? $this->rbac->isAdmin($uid) : false;
        if (!$isAdmin) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $payload = $request->validate([
            'periodo' => ['required', 'string'],
            'vendedor_id' => ['nullable', 'integer', 'min:1'],
            'venta_ids' => ['nullable'],
        ]);

        $periodo = (string) $payload['periodo'];
        if (!preg_match('/^\\d{4}-\\d{2}$/', $periodo)) {
            return response()->json(['ok' => false, 'error' => 'Periodo inválido (YYYY-MM)'], 422);
        }

        $q = DB::table('comisiones')
            ->where('estado', 'PENDIENTE')
            ->where('periodo', $periodo);
        if (!empty($payload['vendedor_id'])) {
            $q->where('vendedor_id', (int) $payload['vendedor_id']);
        }

        $ventaIds = $this->parseVentaIds($payload['venta_ids'] ?? null);
        if ($ventaIds) {
            $q->whereIn('venta_id', $ventaIds);
        }

        $ids = $q->pluck('id')->all();
        if (!$ids) {
            $msg = $ventaIds
                ? 'No hay comisiones PENDIENTES para las ventas seleccionadas.'
                : 'No hay comisiones PENDIENTES en el periodo';
            return response()->json(['ok' => false, 'error' => $msg], 422);
        }

        DB::table('comisiones')->whereIn('id', $ids)->update([
            'estado' => 'APROBADA',
            'aprobado_por' => $uid,
            'aprobado_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('audit_log')->insert([
            'usuario_id' => $uid,
            'accion' => 'comisiones_aprobadas',
            'entidad' => 'comisiones',
            'entidad_id' => $periodo,
            'payload' => DB::raw("'{}'::jsonb"),
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true, 'data' => ['count' => count($ids), 'venta_ids' => $ventaIds]]);
    }

    public function pagar(Request $request): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        $isAdmin = $uid > 0 ? $this->rbac->isAdmin($uid) : false;
        if (!$isAdmin) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $payload = $request->validate([
            'periodo' => ['required', 'string'],
            'vendedor_id' => ['nullable', 'integer', 'min:1'],
            'referencia' => ['nullable', 'string', 'max:100'],
            'venta_ids' => ['nullable'],
        ]);

        $periodo = (string) $payload['periodo'];
        if (!preg_match('/^\\d{4}-\\d{2}$/', $periodo)) {
            return response()->json(['ok' => false, 'error' => 'Periodo inválido (YYYY-MM)'], 422);
        }

        $q = DB::table('comisiones')->where('estado', 'APROBADA')->where('periodo', $periodo);
        if (!empty($payload['vendedor_id'])) {
            $q->where('vendedor_id', (int) $payload['vendedor_id']);
        }

        $ventaIds = $this->parseVentaIds($payload['venta_ids'] ?? null);
        if ($ventaIds) {
            $q->whereIn('venta_id', $ventaIds);
        }

        $ids = $q->pluck('id')->all();
        if (!$ids) {
            $msg = $ventaIds
                ? 'No hay comisiones APROBADAS para las ventas seleccionadas.'
                : 'No hay comisiones APROBADAS en el periodo';
            return response()->json(['ok' => false, 'error' => $msg], 422);
        }

        DB::table('comisiones')->whereIn('id', $ids)->update([
            'estado' => 'PAGADA',
            'pagado_at' => now(),
            'pago_referencia' => $payload['referencia'] ?? null,
            'updated_at' => now(),
        ]);

        DB::table('audit_log')->insert([
            'usuario_id' => $uid,
            'accion' => 'comisiones_pagadas',
            'entidad' => 'comisiones',
            'entidad_id' => $periodo,
            'payload' => DB::raw("'{}'::jsonb"),
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true, 'data' => ['count' => count($ids), 'venta_ids' => $ventaIds]]);
    }

    public function liquidar(Request $request): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        $isAdmin = $uid > 0 ? $this->rbac->isAdmin($uid) : false;
        if (!$isAdmin) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $payload = $request->validate([
            'periodo' => ['required', 'string'],
            'vendedor_id' => ['nullable', 'integer', 'min:1'],
            'porcentaje' => ['nullable', 'numeric', 'min:0'],
            'instalador_fee' => ['nullable', 'numeric', 'min:0'],
            'referencia' => ['nullable', 'string', 'max:100'],
            'venta_ids' => ['required'],
            'fees' => ['nullable', 'array'],
        ]);

        $periodo = (string) $payload['periodo'];
        if (!preg_match('/^\\d{4}-\\d{2}$/', $periodo)) {
            return response()->json(['ok' => false, 'error' => 'Periodo inválido (YYYY-MM)'], 422);
        }

        $ventaIds = $this->parseVentaIds($payload['venta_ids'] ?? null);
        if (!$ventaIds) {
            return response()->json(['ok' => false, 'error' => 'Debes seleccionar al menos un documento.'], 422);
        }

        $porcentaje = isset($payload['porcentaje']) ? (float) ($payload['porcentaje'] ?? 0) : 0.0;
        $instaladorFee = isset($payload['instalador_fee']) ? (float) ($payload['instalador_fee'] ?? 0) : 0.0;

        $from = "{$periodo}-01";
        $to = date('Y-m-d', strtotime("{$from} +1 month"));

        $ventasQ = DB::table('ventas')
            ->where('estado', 'PAGADA')
            ->where('fecha_venta', '>=', $from)
            ->where('fecha_venta', '<', $to)
            ->whereIn('id', $ventaIds);
        if (!empty($payload['vendedor_id'])) {
            $ventasQ->where('vendedor_id', (int) $payload['vendedor_id']);
        }

        $ventas = $ventasQ->get([
            'id',
            'venta_codigo',
            'vendedor_id',
            'tipo_documento',
            'igv_porcentaje',
            'moneda',
            'total',
            'total_pen',
            'fecha_venta',
        ]);

        if ($ventas->isEmpty()) {
            return response()->json(['ok' => false, 'error' => 'No hay ventas PAGADAS en el periodo para la selección.'], 422);
        }

        $now = now();
        $ref = $payload['referencia'] ?? null;
        $vendedorFilter = !empty($payload['vendedor_id']) ? (int) $payload['vendedor_id'] : null;

        $feesPayload = is_array($payload['fees'] ?? null) ? (array) ($payload['fees'] ?? []) : [];

        $result = DB::transaction(function () use ($ventas, $ventaIds, $periodo, $porcentaje, $instaladorFee, $uid, $now, $ref, $vendedorFilter, $feesPayload) {
            $existing = DB::table('comisiones')->whereIn('venta_id', $ventaIds)->get([
                'id', 'venta_id', 'estado', 'monto_pen',
            ])->keyBy('venta_id');

            $paid = 0;
            $skippedPaid = 0;
            $created = 0;

            foreach ($ventas as $v) {
                $ventaId = (int) $v->id;
                $vendedorId = (int) ($v->vendedor_id ?? 0);
                if ($vendedorFilter && $vendedorId !== $vendedorFilter) {
                    continue;
                }

                $totalPen = (float) ($v->total_pen ?? $v->total ?? 0);
                $igvPct = (float) ($v->igv_porcentaje ?? 18);
                $tipoDoc = (string) ($v->tipo_documento ?? 'NOTA_VENTA');

                $feeApplied = $instaladorFee;
                if (array_key_exists((string) $ventaId, $feesPayload)) {
                    $feeApplied = max(0.0, (float) ($feesPayload[(string) $ventaId] ?? 0));
                } elseif (array_key_exists($ventaId, $feesPayload)) {
                    $feeApplied = max(0.0, (float) ($feesPayload[$ventaId] ?? 0));
                }
                $saldo = max(0.0, $totalPen - $feeApplied);
                $base = $saldo;
                $igv = 0.0;

                $isFacturaBoleta = in_array($tipoDoc, ['FACTURA', 'BOLETA'], true);
                if ($isFacturaBoleta) {
                    $div = 1 + max(0.0, $igvPct) / 100;
                    $base = $div > 0 ? ($saldo / $div) : $saldo;
                    $igv = max(0.0, $saldo - $base);
                }

                $base = round($base, 2);
                $igv = round($igv, 2);

                $pct = $porcentaje > 0 ? $porcentaje : $this->resolvePorcentaje($vendedorId, (string) $v->fecha_venta);
                $monto = $pct > 0 ? round($base * ($pct / 100), 2) : 0.0;

                $row = $existing->get($ventaId);
                // Regla: si ya está PAGADA con un monto > 0, no permitir recalcular/pagar.
                // Si está "PAGADA" pero monto es null/0 (deuda técnica por ejecuciones previas), se permite corregir.
                $rowEstado = $row ? (string) ($row->estado ?? '') : '';
                $rowMonto = $row ? (float) ($row->monto_pen ?? 0) : 0.0;
                if ($row && $rowEstado === 'PAGADA' && $rowMonto > 0) {
                    $skippedPaid++;
                    continue;
                }

                $data = [
                    'venta_id' => $ventaId,
                    'vendedor_id' => $vendedorId,
                    'tipo_documento' => $tipoDoc,
                    'base_calculo' => $base,
                    'porcentaje' => $pct > 0 ? $pct : null,
                    'monto_comision' => $monto,
                    'moneda' => 'PEN',
                    'monto_pen' => $monto,
                    'estado' => 'PAGADA',
                    'periodo' => $periodo,
                    'aprobado_por' => $uid,
                    'aprobado_at' => $now,
                    'pagado_at' => $now,
                    'pago_referencia' => $ref,
                    'updated_at' => $now,
                ];

                if ($row) {
                    DB::table('comisiones')->where('id', (int) $row->id)->update($data);
                    $paid++;
                } else {
                    $data['created_at'] = $now;
                    DB::table('comisiones')->insert($data);
                    $created++;
                    $paid++;
                }
            }

            return [
                'paid' => $paid,
                'created' => $created,
                'skipped_paid' => $skippedPaid,
            ];
        });

        DB::table('audit_log')->insert([
            'usuario_id' => $uid,
            'accion' => 'comisiones_liquidadas',
            'entidad' => 'comisiones',
            'entidad_id' => $periodo,
            'payload' => DB::raw("'".str_replace("'", "''", json_encode([
                'venta_ids' => $ventaIds,
                'vendedor_id' => $payload['vendedor_id'] ?? null,
                'porcentaje' => $porcentaje,
                'instalador_fee' => $instaladorFee,
                'referencia' => $payload['referencia'] ?? null,
                'result' => $result,
            ]) ?: '{}')."'::jsonb"),
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true, 'data' => ['count' => (int) ($result['paid'] ?? 0), 'venta_ids' => $ventaIds, 'meta' => $result]]);
    }

    public function export(Request $request): StreamedResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        $user = $uid > 0 ? DB::table('usuarios')->leftJoin('roles', 'roles.id', '=', 'usuarios.rol_id')->where('usuarios.id', $uid)->select(['usuarios.*', 'roles.codigo as rol_codigo'])->first() : null;
        $isAdmin = $user && (string) ($user->rol_codigo ?? '') === 'administrador';

        $periodo = (string) $request->query('periodo', '');
        $q = DB::table('comisiones')->orderBy('id', 'desc');
        if ($periodo !== '') {
            $q->where('periodo', $periodo);
        }
        if (!$isAdmin && $uid > 0) {
            $q->where('vendedor_id', $uid);
        }

        $filename = 'comisiones.csv';

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'venta_id', 'vendedor_id', 'tipo_documento', 'base_calculo', 'monto_comision', 'moneda', 'monto_pen', 'estado', 'periodo']);
            foreach ($q->cursor() as $r) {
                fputcsv($out, [
                    $r->id,
                    $r->venta_id,
                    $r->vendedor_id,
                    $r->tipo_documento,
                    $r->base_calculo,
                    $r->monto_comision,
                    $r->moneda,
                    $r->monto_pen,
                    $r->estado,
                    $r->periodo,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
