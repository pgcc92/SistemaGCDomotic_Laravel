@php
    $kpis = (array) ($dash['kpis'] ?? []);
    $series = (array) ($dash['series'] ?? []);
    $ventas30 = (array) ($series['ventas_30d'] ?? []);
    $ticketsEstado = (array) ($series['tickets_por_estado'] ?? []);

    $money = function ($n, $currency = 'PEN') {
        $n = (float) ($n ?? 0);
        try {
            return (new \NumberFormatter('es_PE', \NumberFormatter::CURRENCY))->formatCurrency($n, strtoupper((string) $currency));
        } catch (\Throwable) {
            return number_format($n, 2);
        }
    };

    $perms = (array) ($perms ?? []);
    $isAdmin = (bool) (($perms['*']['*'] ?? false) === true);
    $can = function (string $mod, string $act = 'ver') use ($perms, $isAdmin): bool {
        return $isAdmin || (bool) ($perms[$mod][$act] ?? false);
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <div class="text-lg font-semibold text-slate-900">Dashboard</div>
            <div class="text-sm text-slate-500">Visión rápida de operación, ventas y stock.</div>
        </div>
    </x-slot>

    <div x-data="{
            ventas30: @js($ventas30),
            ticketsEstado: @js($ticketsEstado),
            init() {
                if (window.ApexCharts) this.renderCharts();
                else this.loadApex();
            },
            loadApex() {
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
                s.onload = () => this.renderCharts();
                document.head.appendChild(s);
            },
            renderCharts() {
                const salesEl = this.$refs.salesChart;
                const tickEl = this.$refs.ticketsChart;
                if (salesEl) {
                    const cats = this.ventas30.map(x => x.date);
                    const vals = this.ventas30.map(x => Number(x.total || 0));
                    new window.ApexCharts(salesEl, {
                        chart: { type: 'area', height: 280, toolbar: { show: false }, zoom: { enabled: false } },
                        stroke: { curve: 'smooth', width: 2 },
                        grid: { borderColor: '#e2e8f0' },
                        colors: ['rgb(var(--gc-primary) / 1)'],
                        dataLabels: { enabled: false },
                        series: [{ name: 'Ventas', data: vals }],
                        xaxis: { categories: cats, labels: { show: false } },
                        yaxis: { labels: { formatter: (v) => (v || 0).toFixed(0) } },
                        fill: { type: 'gradient', gradient: { shadeIntensity: 0.2, opacityFrom: 0.35, opacityTo: 0.05 } },
                        tooltip: { x: { show: false } },
                    }).render();
                }
                if (tickEl && Array.isArray(this.ticketsEstado) && this.ticketsEstado.length) {
                    const labels = this.ticketsEstado.map(x => x.estado || '—');
                    const vals = this.ticketsEstado.map(x => Number(x.c || 0));
                    new window.ApexCharts(tickEl, {
                        chart: { type: 'donut', height: 280 },
                        labels,
                        series: vals,
                        legend: { position: 'bottom' },
                        colors: [
                            'rgb(var(--gc-primary) / 1)',
                            'rgb(var(--gc-secondary) / 1)',
                            '#22c55e',
                            '#f97316',
                            '#ef4444',
                            '#64748b',
                        ],
                        dataLabels: { enabled: false },
                    }).render();
                }
            }
        }"
        x-init="init()"
        class="space-y-6"
    >
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @if($can('agenda','ver'))
                <div class="gc-card p-5 bg-gradient-to-br from-emerald-50 to-white">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-semibold text-emerald-700/80">Agenda</div>
                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200">
                            Hoy: {{ (int) ($kpis['agenda_hoy_count'] ?? 0) }}
                        </span>
                    </div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) ($kpis['agenda_pendientes_count'] ?? 0) }}</div>
                    <div class="mt-1 flex items-center justify-between text-xs text-slate-500">
                        <span>Pendientes visibles.</span>
                        <a href="/agenda" class="font-semibold text-emerald-700 hover:text-emerald-800">Ver agenda</a>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2 text-[11px] text-slate-600">
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">
                            Programadas: {{ (int) ($kpis['agenda_programadas_count'] ?? 0) }}
                        </span>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">
                            Realizadas: {{ (int) ($kpis['agenda_realizadas_count'] ?? 0) }}
                        </span>
                    </div>
                </div>
            @endif

            @if($can('ventas','ver'))
                <div class="gc-card p-5 bg-gradient-to-br from-primary/12 to-white">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-semibold text-primary/80">Ventas del mes</div>
                        <span class="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-semibold text-primary ring-1 ring-inset ring-primary/15">
                            {{ (int) ($kpis['ventas_mes_count'] ?? 0) }} ventas
                        </span>
                    </div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900">{{ $money($kpis['ventas_mes_total'] ?? 0, 'PEN') }}</div>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                        <span>Acumulado del mes (excluye anuladas).</span>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">
                            Pagadas: {{ (int) ($kpis['ventas_mes_pagadas_count'] ?? 0) }}
                        </span>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">
                            Pendientes: {{ (int) ($kpis['ventas_mes_pendientes_count'] ?? 0) }}
                        </span>
                    </div>
                </div>
            @elseif($can('tickets','ver'))
                <div class="gc-card p-5 bg-gradient-to-br from-sky-50 to-white">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-semibold text-sky-700/80">Tickets abiertos</div>
                        <span class="inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-800 ring-1 ring-inset ring-sky-200">Soporte</span>
                    </div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900">
                        {{ $kpis['tickets_abiertos_count'] === null ? '—' : (int) $kpis['tickets_abiertos_count'] }}
                    </div>
                    <div class="mt-1 text-xs text-slate-500">Prioriza asignación y cierre.</div>
                </div>
            @else
                <div class="gc-card p-5 bg-gradient-to-br from-slate-50 to-white">
                    <div class="text-xs font-semibold text-slate-700/80">Bienvenido</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900">Panel</div>
                    <div class="mt-1 text-xs text-slate-500">Tu dashboard se ajusta a tus permisos.</div>
                </div>
            @endif

            @if($can('ventas','ver'))
            <div class="gc-card p-5 bg-gradient-to-br from-emerald-50 to-white">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-semibold text-emerald-700/80">Ventas pagadas (mes)</div>
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200">
                        {{ (int) ($kpis['ventas_mes_pagadas_count'] ?? 0) }}
                    </span>
                </div>
                <div class="mt-2 text-2xl font-semibold text-slate-900">{{ $money($kpis['ventas_mes_pagadas_total'] ?? 0, 'PEN') }}</div>
                <div class="mt-1 text-xs text-slate-500">Caja/ingresos confirmados.</div>
            </div>
            @endif

            @if($can('comisiones','ver'))
            <div class="gc-card p-5 bg-gradient-to-br from-emerald-50 to-white">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-semibold text-emerald-700/80">Comisiones pendientes</div>
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800 ring-1 ring-inset ring-emerald-200">
                        {{ (int) ($kpis['comisiones_pendientes_count'] ?? 0) }}
                    </span>
                </div>
                <div class="mt-2 text-2xl font-semibold text-slate-900">{{ $money($kpis['comisiones_pendientes_total'] ?? 0, 'PEN') }}</div>
                <div class="mt-1 text-xs text-slate-500">Por aprobar / pagar.</div>
            </div>
            @endif

            @if($can('productos','ver'))
            <div class="gc-card p-5 bg-gradient-to-br from-amber-50 to-white">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-semibold text-amber-700/80">Stock bajo</div>
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800 ring-1 ring-inset ring-amber-200">
                        Acción
                    </span>
                </div>
                <div class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) ($kpis['stock_bajo_count'] ?? 0) }}</div>
                <div class="mt-1 flex items-center justify-between text-xs text-slate-500">
                    <span>Productos con stock total ≤ 1.</span>
                    <a href="/productos" class="font-semibold text-amber-700 hover:text-amber-800">Ver productos</a>
                </div>
            </div>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @if($can('tickets','ver'))
            <div class="gc-card p-4">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-semibold text-slate-600">Tickets abiertos</div>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">Soporte</span>
                </div>
                <div class="mt-1 text-xl font-semibold text-slate-900">
                    {{ $kpis['tickets_abiertos_count'] === null ? '—' : (int) $kpis['tickets_abiertos_count'] }}
                </div>
                <div class="mt-0.5 text-xs text-slate-500">Estado ABIERTO.</div>
            </div>
            @endif

            @if($can('ventas','ver'))
            <div class="gc-card p-4">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-semibold text-slate-600">Ventas pendientes (mes)</div>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">Seguimiento</span>
                </div>
                <div class="mt-1 text-xl font-semibold text-slate-900">{{ (int) ($kpis['ventas_mes_pendientes_count'] ?? 0) }}</div>
                <div class="mt-0.5 text-xs text-slate-500">Oportunidades de cobro.</div>
            </div>
            @endif

            <div class="gc-card p-4 sm:col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between">
                    <div class="text-xs font-semibold text-slate-600">Acciones rápidas</div>
                    <span class="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-semibold text-primary ring-1 ring-inset ring-primary/15">Atajos</span>
                </div>
                <div class="mt-2 flex flex-wrap gap-2 text-sm">
                    @if($can('ventas','ver')) <a href="/ventas" class="rounded-lg border border-slate-200 bg-white px-3 py-2 hover:bg-slate-50">Ventas</a> @endif
                    @if($can('productos','ver')) <a href="/productos" class="rounded-lg border border-slate-200 bg-white px-3 py-2 hover:bg-slate-50">Productos</a> @endif
                    @if($can('tickets','ver')) <a href="/tickets" class="rounded-lg border border-slate-200 bg-white px-3 py-2 hover:bg-slate-50">Tickets</a> @endif
                    @if($can('clientes','ver')) <a href="/clientes" class="rounded-lg border border-slate-200 bg-white px-3 py-2 hover:bg-slate-50">Clientes</a> @endif
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            @if($can('ventas','ver'))
            <div class="gc-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Ventas (30 días)</div>
                        <div class="mt-0.5 text-xs text-slate-500">Solo ventas pagadas.</div>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">Area</span>
                </div>
                <div class="mt-4" x-ref="salesChart"></div>
            </div>
            @endif

            @if($can('tickets','ver'))
            <div class="gc-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Tickets por estado</div>
                        <div class="mt-0.5 text-xs text-slate-500">Distribución actual.</div>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">Donut</span>
                </div>
                <div class="mt-4" x-ref="ticketsChart"></div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
