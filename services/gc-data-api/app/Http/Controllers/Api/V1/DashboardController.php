<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Rbac\RbacService;
use App\Infrastructure\Db\SchemaIntrospector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class DashboardController
{
    public function __construct(
        private readonly SchemaIntrospector $schema,
        private readonly RbacService $rbac,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $ventas = (string) config('gc.tables.ventas', 'ventas');
        $tickets = (string) config('gc.tables.tickets', 'tickets');
        $comisiones = (string) config('gc.tables.comisiones', 'comisiones');
        $productos = (string) config('gc.tables.productos', 'productos');
        $stockSucursal = (string) config('gc.tables.stock_sucursal', 'stock_sucursal');
        $agenda = (string) config('gc.tables.agenda_instalaciones', 'agenda_instalaciones');
        $ventaItems = (string) config('gc.tables.venta_items', 'venta_items');

        $today = now();
        $startMonth = $today->copy()->startOfMonth()->startOfDay();
        $endMonth = $today->copy()->endOfMonth()->endOfDay();
        $startPreviousMonth = $today->copy()->subMonthNoOverflow()->startOfMonth()->startOfDay();
        $endPreviousMonth = $today->copy()->subMonthNoOverflow()->endOfMonth()->endOfDay();
        $startYear = $today->copy()->startOfYear()->startOfDay();
        $endYear = $today->copy()->endOfYear()->endOfDay();
        $startPreviousYear = $today->copy()->subYear()->startOfYear()->startOfDay();
        $endPreviousYearComparable = $today->copy()->subYear()->endOfDay();
        $start30 = $today->copy()->subDays(29)->startOfDay();
        $end30 = $today->copy()->endOfDay();

        $uid = (int) $request->attributes->get('remote_uid', 0);
        $user = $uid > 0
            ? DB::table('usuarios')->where('usuarios.id', $uid)->select(['usuarios.*'])->first()
            : null;
        $isAdmin = $uid > 0 ? $this->rbac->isAdmin($uid) : false;
        $tecnicoId = $user ? (int) ($user->tecnico_id ?? 0) : 0;
        $canViewAllVentas = $uid > 0 ? $this->rbac->canViewAll($uid, 'ventas') : false;
        $canViewAllComisiones = $uid > 0 ? $this->rbac->canViewAll($uid, 'comisiones') : false;
        $canViewAllTickets = $uid > 0 ? $this->rbac->canViewAll($uid, 'tickets') : false;
        $canViewAllAgenda = $uid > 0 ? $this->rbac->canViewAll($uid, 'agenda') : false;

        $kpis = [
            // Ventas (mes)
            'ventas_mes_total' => 0.0,
            'ventas_mes_count' => 0,
            'ventas_mes_pagadas_total' => 0.0,
            'ventas_mes_pagadas_count' => 0,
            'ventas_mes_pendientes_count' => 0,
            'ventas_mes_anterior_total' => 0.0,
            'ventas_mes_anterior_count' => 0,
            'ventas_mes_variacion_pct' => null,
            'ventas_anio_total' => 0.0,
            'ventas_anio_count' => 0,
            'ventas_anio_pagadas_total' => 0.0,
            'ventas_anio_promedio_mensual' => 0.0,
            'ventas_anio_proyeccion' => 0.0,
            'ventas_anio_anterior_comparable_total' => 0.0,
            'ventas_anio_variacion_pct' => null,
            'comisiones_pendientes_count' => 0,
            'comisiones_pendientes_total' => 0.0,
            'stock_bajo_count' => 0,
            'tickets_abiertos_count' => null,
            // Agenda (mes)
            'agenda_pendientes_count' => null,
            'agenda_programadas_count' => null,
            'agenda_realizadas_count' => null,
            'agenda_hoy_count' => null,
        ];

        $series = [
            'ventas_30d' => [],
            'ventas_anio' => [],
            'tickets_por_estado' => [],
        ];
        $rankings = [
            'productos_periodo' => $today->format('Y'),
            'productos_mas_vendidos' => [],
            'productos_menos_vendidos' => [],
        ];
        $documentos = [
            'actual' => $this->emptyDocumentosVentas($today->format('Y-m')),
            'anterior' => $this->emptyDocumentosVentas($today->copy()->subMonthNoOverflow()->format('Y-m')),
        ];

        if ($this->schema->hasTable($ventas)) {
            // Ventas no anuladas: todas las métricas comerciales usan esta misma regla y monto PEN.
            $qTotal = $this->ventasComercialesQuery($ventas, $startMonth, $endMonth, $canViewAllVentas, $uid);
            $aggTotal = $this->ventasAggregate($qTotal);
            $kpis['ventas_mes_count'] = (int) ($aggTotal->c ?? 0);
            $kpis['ventas_mes_total'] = (float) ($aggTotal->t ?? 0);

            $qPreviousMonth = $this->ventasComercialesQuery($ventas, $startPreviousMonth, $endPreviousMonth, $canViewAllVentas, $uid);
            $aggPreviousMonth = $this->ventasAggregate($qPreviousMonth);
            $previousMonthTotal = (float) ($aggPreviousMonth->t ?? 0);
            $kpis['ventas_mes_anterior_count'] = (int) ($aggPreviousMonth->c ?? 0);
            $kpis['ventas_mes_anterior_total'] = $previousMonthTotal;
            $kpis['ventas_mes_variacion_pct'] = $previousMonthTotal > 0
                ? round((($kpis['ventas_mes_total'] - $previousMonthTotal) / $previousMonthTotal) * 100, 1)
                : null;
            $documentos['actual'] = $this->documentosVentas($ventas, $startMonth, $endMonth, $canViewAllVentas, $uid, $today->format('Y-m'));
            $documentos['anterior'] = $this->documentosVentas($ventas, $startPreviousMonth, $endPreviousMonth, $canViewAllVentas, $uid, $today->copy()->subMonthNoOverflow()->format('Y-m'));
            // Mantener las tarjetas ejecutivas alineadas exactamente con la composición por documento.
            $kpis['ventas_mes_count'] = (int) ($documentos['actual']['total_count'] ?? $kpis['ventas_mes_count']);
            $kpis['ventas_mes_total'] = (float) ($documentos['actual']['total'] ?? $kpis['ventas_mes_total']);
            $kpis['ventas_mes_anterior_count'] = (int) ($documentos['anterior']['total_count'] ?? $kpis['ventas_mes_anterior_count']);
            $kpis['ventas_mes_anterior_total'] = (float) ($documentos['anterior']['total'] ?? $kpis['ventas_mes_anterior_total']);
            $previousMonthTotal = (float) $kpis['ventas_mes_anterior_total'];
            $kpis['ventas_mes_variacion_pct'] = $previousMonthTotal > 0
                ? round((($kpis['ventas_mes_total'] - $previousMonthTotal) / $previousMonthTotal) * 100, 1)
                : null;

            $qPag = DB::table($ventas)
                ->whereRaw("upper(coalesce(estado,'')) = 'PAGADA'")
                ->whereBetween('fecha_venta', [$startMonth, $endMonth]);
            if (!$canViewAllVentas && $uid > 0) {
                $qPag->where('vendedor_id', $uid);
            }
            $agg = $qPag->selectRaw('count(*)::int as c, ' . $this->ventasAmountSumSql() . ' as t')->first();
            $kpis['ventas_mes_pagadas_count'] = (int) ($agg->c ?? 0);
            $kpis['ventas_mes_pagadas_total'] = (float) ($agg->t ?? 0);

            $qPen = DB::table($ventas)->whereRaw("upper(coalesce(estado,'')) = 'PENDIENTE'")->whereBetween('fecha_venta', [$startMonth, $endMonth]);
            if (!$canViewAllVentas && $uid > 0) {
                $qPen->where('vendedor_id', $uid);
            }
            $kpis['ventas_mes_pendientes_count'] = (int) $qPen->count();

            $qYear = $this->ventasComercialesQuery($ventas, $startYear, $endYear, $canViewAllVentas, $uid);
            $aggYear = $this->ventasAggregate($qYear);
            $kpis['ventas_anio_count'] = (int) ($aggYear->c ?? 0);
            $kpis['ventas_anio_total'] = (float) ($aggYear->t ?? 0);
            $elapsedMonths = max(1, (int) $today->format('n'));
            $kpis['ventas_anio_promedio_mensual'] = round($kpis['ventas_anio_total'] / $elapsedMonths, 2);
            $kpis['ventas_anio_proyeccion'] = round($kpis['ventas_anio_promedio_mensual'] * 12, 2);

            $qYearPaid = DB::table($ventas)
                ->whereRaw("upper(coalesce(estado,'')) = 'PAGADA'")
                ->whereBetween('fecha_venta', [$startYear, $endYear]);
            if (!$canViewAllVentas && $uid > 0) {
                $qYearPaid->where('vendedor_id', $uid);
            }
            $kpis['ventas_anio_pagadas_total'] = (float) $qYearPaid->selectRaw($this->ventasAmountSumSql() . ' as t')->value('t');

            $qPreviousYear = $this->ventasComercialesQuery($ventas, $startPreviousYear, $endPreviousYearComparable, $canViewAllVentas, $uid);
            $previousYearTotal = (float) $qPreviousYear->selectRaw($this->ventasAmountSumSql() . ' as t')->value('t');
            $kpis['ventas_anio_anterior_comparable_total'] = $previousYearTotal;
            $kpis['ventas_anio_variacion_pct'] = $previousYearTotal > 0
                ? round((($kpis['ventas_anio_total'] - $previousYearTotal) / $previousYearTotal) * 100, 1)
                : null;

            $qYearSeries = $this->ventasComercialesQuery($ventas, $startYear, $endYear, $canViewAllVentas, $uid);
            $rowsYear = $qYearSeries
                ->selectRaw("to_char(fecha_venta::date,'YYYY-MM') as m, " . $this->ventasAmountSumSql() . " as t")
                ->groupByRaw("to_char(fecha_venta::date,'YYYY-MM')")
                ->orderByRaw("to_char(fecha_venta::date,'YYYY-MM')")
                ->get();
            $yearMap = [];
            foreach ($rowsYear as $row) {
                $yearMap[(string) $row->m] = (float) $row->t;
            }
            for ($month = 1; $month <= 12; $month++) {
                $key = $today->copy()->month($month)->format('Y-m');
                $series['ventas_anio'][] = [
                    'month' => $key,
                    'label' => $today->copy()->month($month)->locale('es')->translatedFormat('M'),
                    'total' => (float) ($yearMap[$key] ?? 0.0),
                ];
            }

            // Serie 30 días (pagadas)
            $q30 = DB::table($ventas)
                ->whereRaw("upper(coalesce(estado,'')) = 'PAGADA'")
                ->whereBetween('fecha_venta', [$start30, $end30]);
            if (!$canViewAllVentas && $uid > 0) {
                $q30->where('vendedor_id', $uid);
            }
            $rows30 = $q30->selectRaw("to_char(fecha_venta::date,'YYYY-MM-DD') as d, " . $this->ventasAmountSumSql() . " as t")
                ->groupByRaw("fecha_venta::date")
                ->orderByRaw("fecha_venta::date")
                ->get();

            $map = [];
            foreach ($rows30 as $r) {
                $map[(string) $r->d] = (float) $r->t;
            }

            $cursor = $start30->copy();
            while ($cursor->lte($end30)) {
                $key = $cursor->format('Y-m-d');
                $series['ventas_30d'][] = [
                    'date' => $key,
                    'total' => (float) ($map[$key] ?? 0.0),
                ];
                $cursor->addDay();
            }

            if ($this->schema->hasTable($ventaItems) && $this->schema->hasTable($productos)) {
                $productsQuery = DB::table("{$ventaItems} as vi")
                    ->join("{$ventas} as v", 'v.id', '=', 'vi.venta_id')
                    ->join("{$productos} as p", 'p.id', '=', 'vi.producto_id')
                    ->whereNotNull('vi.producto_id')
                    ->whereRaw("upper(coalesce(v.estado,'')) <> 'ANULADA'")
                    ->whereBetween('v.fecha_venta', [$startYear, $endYear]);
                if (!$canViewAllVentas && $uid > 0) {
                    $productsQuery->where('v.vendedor_id', $uid);
                }
                $productSales = $productsQuery
                    ->selectRaw('p.id, p.sku, p.nombre, p.modelo, coalesce(sum(vi.cantidad),0)::int as unidades, coalesce(sum(vi.total),0)::numeric as importe')
                    ->groupBy(['p.id', 'p.sku', 'p.nombre', 'p.modelo']);
                $rankings['productos_mas_vendidos'] = (clone $productSales)->orderByDesc('unidades')->orderByDesc('importe')->limit(5)->get();
                $rankings['productos_menos_vendidos'] = (clone $productSales)->orderBy('unidades')->orderBy('importe')->limit(5)->get();
            }
        }

        if ($this->schema->hasTable($comisiones)) {
            $qCom = DB::table($comisiones)->where('estado', 'PENDIENTE');
            if (!$canViewAllComisiones && $uid > 0) {
                $qCom->where('vendedor_id', $uid);
            }
            $agg = $qCom->selectRaw('count(*)::int as c, coalesce(sum(monto_pen), sum(monto_comision),0)::numeric as t')->first();
            $kpis['comisiones_pendientes_count'] = (int) ($agg->c ?? 0);
            $kpis['comisiones_pendientes_total'] = (float) ($agg->t ?? 0);
        }

        if ($this->schema->hasTable($productos) && $this->schema->hasTable($stockSucursal)) {
            // Stock bajo: total stock <= 1 (regla simple y operativa).
            $rows = DB::table($stockSucursal)
                ->selectRaw('producto_id, coalesce(sum(stock),0)::int as st')
                ->groupBy('producto_id')
                ->havingRaw('coalesce(sum(stock),0) <= 1')
                ->get();
            $kpis['stock_bajo_count'] = $rows->count();
        }

        if ($this->schema->hasTable($tickets)) {
            $qTick = DB::table($tickets)->where('estado', 'ABIERTO');
            if (!$canViewAllTickets && $tecnicoId > 0) {
                $qTick->where('tecnico_asignado', $tecnicoId);
            }
            $kpis['tickets_abiertos_count'] = (int) $qTick->count();

            $qDist = DB::table($tickets);
            if (!$canViewAllTickets && $tecnicoId > 0) {
                $qDist->where('tecnico_asignado', $tecnicoId);
            }
            $rows = $qDist->selectRaw('estado, count(*)::int as c')->groupBy('estado')->orderByDesc('c')->get();
            $series['tickets_por_estado'] = $rows;
        }

        if ($this->schema->hasTable($agenda)) {
            $qAgenda = DB::table($agenda);
            if (!$canViewAllAgenda && $uid > 0) {
                $qAgenda->where('tecnico_id', $uid);
            }
            $kpis['agenda_pendientes_count'] = (int) (clone $qAgenda)->where('estado', 'PENDIENTE')->count();
            $kpis['agenda_programadas_count'] = (int) (clone $qAgenda)->where('estado', 'PROGRAMADA')->count();
            $kpis['agenda_realizadas_count'] = (int) (clone $qAgenda)->where('estado', 'REALIZADA')->count();
            $kpis['agenda_hoy_count'] = (int) (clone $qAgenda)
                ->whereBetween('fecha_programada', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
                ->count();
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'kpis' => $kpis,
                'series' => $series,
                'rankings' => $rankings,
                'documentos' => $documentos,
            ],
        ]);
    }

    /**
     * @param mixed $start
     * @param mixed $end
     * @return array<string,mixed>
     */
    private function documentosVentas(string $ventas, mixed $start, mixed $end, bool $canViewAllVentas, int $uid, string $periodo): array
    {
        $query = $this->ventasComercialesQuery($ventas, $start, $end, $canViewAllVentas, $uid);

        $case = "case when upper(coalesce(tipo_documento,'')) in ('FACTURA','BOLETA') then 'factura_boleta' else 'nota_venta' end";
        $rows = $query
            ->selectRaw("{$case} as grupo, count(*)::int as c, " . $this->ventasAmountSumSql() . " as t")
            ->groupByRaw($case)
            ->get();

        $data = $this->emptyDocumentosVentas($periodo);
        foreach ($rows as $row) {
            $group = (string) ($row->grupo ?? '');
            if (!isset($data[$group])) {
                continue;
            }
            $data[$group]['count'] = (int) ($row->c ?? 0);
            $data[$group]['total'] = (float) ($row->t ?? 0);
            $data['total_count'] += (int) ($row->c ?? 0);
            $data['total'] += (float) ($row->t ?? 0);
        }

        foreach (['factura_boleta', 'nota_venta'] as $group) {
            $data[$group]['mix_pct'] = $data['total'] > 0
                ? round(((float) $data[$group]['total'] / (float) $data['total']) * 100, 1)
                : 0.0;
        }

        return $data;
    }

    /**
     * @param mixed $start
     * @param mixed $end
     */
    private function ventasComercialesQuery(string $ventas, mixed $start, mixed $end, bool $canViewAllVentas, int $uid): \Illuminate\Database\Query\Builder
    {
        $query = DB::table($ventas)
            ->whereRaw("upper(coalesce(estado,'')) <> 'ANULADA'")
            ->whereBetween('fecha_venta', [$start, $end]);

        if (!$canViewAllVentas && $uid > 0) {
            $query->where('vendedor_id', $uid);
        }

        return $query;
    }

    private function ventasAggregate(\Illuminate\Database\Query\Builder $query): object
    {
        return $query->selectRaw('count(*)::int as c, ' . $this->ventasAmountSumSql() . ' as t')->first();
    }

    private function ventasAmountSumSql(): string
    {
        return 'round(coalesce(sum(coalesce(total_pen, total, 0)),0)::numeric, 2)';
    }

    /**
     * @return array<string,mixed>
     */
    private function emptyDocumentosVentas(string $periodo): array
    {
        return [
            'periodo' => $periodo,
            'total' => 0.0,
            'total_count' => 0,
            'factura_boleta' => [
                'label' => 'Factura / Boleta',
                'total' => 0.0,
                'count' => 0,
                'mix_pct' => 0.0,
            ],
            'nota_venta' => [
                'label' => 'Nota de venta',
                'total' => 0.0,
                'count' => 0,
                'mix_pct' => 0.0,
            ],
        ];
    }
}
