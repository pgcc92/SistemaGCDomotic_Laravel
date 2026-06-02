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

        $today = now();
        $startMonth = $today->copy()->startOfMonth()->startOfDay();
        $endMonth = $today->copy()->endOfMonth()->endOfDay();
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
            'tickets_por_estado' => [],
        ];

        if ($this->schema->hasTable($ventas)) {
            // Total del mes (todo lo vendido, excluye ANULADA).
            $qTotal = DB::table($ventas)
                ->whereNotIn('estado', ['ANULADA'])
                ->whereBetween('fecha_venta', [$startMonth, $endMonth]);
            if (!$canViewAllVentas && $uid > 0) {
                // Vendedor no-admin: solo sus ventas
                $qTotal->where('vendedor_id', $uid);
            }
            $aggTotal = $qTotal->selectRaw('count(*)::int as c, coalesce(sum(total),0)::numeric as t')->first();
            $kpis['ventas_mes_count'] = (int) ($aggTotal->c ?? 0);
            $kpis['ventas_mes_total'] = (float) ($aggTotal->t ?? 0);

            $qPag = DB::table($ventas)
                ->where('estado', 'PAGADA')
                ->whereBetween('fecha_venta', [$startMonth, $endMonth]);
            if (!$canViewAllVentas && $uid > 0) {
                $qPag->where('vendedor_id', $uid);
            }
            $agg = $qPag->selectRaw('count(*)::int as c, coalesce(sum(total),0)::numeric as t')->first();
            $kpis['ventas_mes_pagadas_count'] = (int) ($agg->c ?? 0);
            $kpis['ventas_mes_pagadas_total'] = (float) ($agg->t ?? 0);

            $qPen = DB::table($ventas)->where('estado', 'PENDIENTE')->whereBetween('fecha_venta', [$startMonth, $endMonth]);
            if (!$canViewAllVentas && $uid > 0) {
                $qPen->where('vendedor_id', $uid);
            }
            $kpis['ventas_mes_pendientes_count'] = (int) $qPen->count();

            // Serie 30 días (pagadas)
            $q30 = DB::table($ventas)
                ->where('estado', 'PAGADA')
                ->whereBetween('fecha_venta', [$start30, $end30]);
            if (!$canViewAllVentas && $uid > 0) {
                $q30->where('vendedor_id', $uid);
            }
            $rows30 = $q30->selectRaw("to_char(fecha_venta::date,'YYYY-MM-DD') as d, coalesce(sum(total),0)::numeric as t")
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
            ],
        ]);
    }
}
