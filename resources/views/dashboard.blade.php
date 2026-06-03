@php
    $kpis = (array) ($dash['kpis'] ?? []);
    $series = (array) ($dash['series'] ?? []);
    $rankings = (array) ($dash['rankings'] ?? []);
    $ventas30 = (array) ($series['ventas_30d'] ?? []);
    $ventasAnio = (array) ($series['ventas_anio'] ?? []);
    $ticketsEstado = (array) ($series['tickets_por_estado'] ?? []);
    $masVendidos = (array) ($rankings['productos_mas_vendidos'] ?? []);
    $menosVendidos = (array) ($rankings['productos_menos_vendidos'] ?? []);
    $documentos = (array) ($dash['documentos'] ?? []);
    $docActual = (array) ($documentos['actual'] ?? []);
    $docAnterior = (array) ($documentos['anterior'] ?? []);
    $docActualFactura = (array) ($docActual['factura_boleta'] ?? []);
    $docActualNota = (array) ($docActual['nota_venta'] ?? []);
    $docAnteriorFactura = (array) ($docAnterior['factura_boleta'] ?? []);
    $docAnteriorNota = (array) ($docAnterior['nota_venta'] ?? []);

    $money = function ($value, $currency = 'PEN') {
        $value = (float) ($value ?? 0);
        try {
            return (new \NumberFormatter('es_PE', \NumberFormatter::CURRENCY))->formatCurrency($value, strtoupper((string) $currency));
        } catch (\Throwable) {
            return 'S/ ' . number_format($value, 2);
        }
    };
    $number = fn ($value) => number_format((float) ($value ?? 0), 2);
    $perms = (array) ($perms ?? []);
    $isAdmin = (bool) (($perms['*']['*'] ?? false) === true);
    $can = function (string $module, string $action = 'ver') use ($perms, $isAdmin): bool {
        return $isAdmin || (bool) ($perms[$module][$action] ?? false);
    };

    $ventasMes = (float) ($kpis['ventas_mes_total'] ?? 0);
    $ventasMesAnterior = (float) ($kpis['ventas_mes_anterior_total'] ?? 0);
    $ventasPagadas = (float) ($kpis['ventas_mes_pagadas_total'] ?? 0);
    $ventasAnioTotal = (float) ($kpis['ventas_anio_total'] ?? 0);
    $ventasAnioPagadas = (float) ($kpis['ventas_anio_pagadas_total'] ?? 0);
    $variacionMes = $kpis['ventas_mes_variacion_pct'] ?? null;
    $variacionAnio = $kpis['ventas_anio_variacion_pct'] ?? null;
    $avanceCaja = $ventasMes > 0 ? min(100, round(($ventasPagadas / $ventasMes) * 100)) : 0;
    $avanceCajaAnual = $ventasAnioTotal > 0 ? min(100, round(($ventasAnioPagadas / $ventasAnioTotal) * 100)) : 0;
    $alertasOperativas = (int) ($kpis['tickets_abiertos_count'] ?? 0)
        + (int) ($kpis['agenda_pendientes_count'] ?? 0)
        + (int) ($kpis['stock_bajo_count'] ?? 0);
    $bestProduct = (array) ($masVendidos[0] ?? []);
    $monthName = now()->locale('es')->translatedFormat('F Y');
    $docChange = function ($actual, $anterior) {
        $actual = (float) ($actual ?? 0);
        $anterior = (float) ($anterior ?? 0);
        return $anterior > 0 ? round((($actual - $anterior) / $anterior) * 100, 1) : null;
    };
    $facturaChange = $docChange($docActualFactura['total'] ?? 0, $docAnteriorFactura['total'] ?? 0);
    $notaChange = $docChange($docActualNota['total'] ?? 0, $docAnteriorNota['total'] ?? 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <div class="text-lg font-semibold text-slate-900 dark:text-slate-100">Dashboard</div>
            <div class="text-sm text-slate-500 dark:text-slate-400">{{ $isAdmin ? 'Control ejecutivo comercial y operativo.' : 'Visión rápida de tu operación.' }}</div>
        </div>
    </x-slot>

    <div
        x-data="{
            ventas30: @js($ventas30),
            ventasAnio: @js($ventasAnio),
            ticketsEstado: @js($ticketsEstado),
            init() {
                if (window.ApexCharts) this.renderCharts();
                else {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
                    script.onload = () => this.renderCharts();
                    document.head.appendChild(script);
                }
            },
            area(el, rows, key, label) {
                if (!el) return;
                el.innerHTML = '';
                const isDark = document.documentElement.classList.contains('dark');
                new window.ApexCharts(el, {
                    chart: { type: 'area', height: 245, toolbar: { show: false }, zoom: { enabled: false }, foreColor: isDark ? '#cbd5e1' : '#475569' },
                    stroke: { curve: 'smooth', width: 2.5 },
                    grid: { borderColor: isDark ? '#334155' : '#e2e8f0', strokeDashArray: 4 },
                    colors: ['rgb(var(--gc-primary) / 1)'],
                    dataLabels: { enabled: false },
                    series: [{ name: label, data: rows.map(row => Number(row.total || 0)) }],
                    xaxis: { categories: rows.map(row => row[key]), labels: { style: { colors: isDark ? '#94a3b8' : '#64748b', fontSize: '11px' } } },
                    yaxis: { labels: { formatter: value => 'S/ ' + Number(value || 0).toLocaleString('es-PE', { maximumFractionDigits: 0 }) } },
                    fill: { type: 'gradient', gradient: { shadeIntensity: 0.2, opacityFrom: 0.3, opacityTo: 0.03 } },
                    theme: { mode: isDark ? 'dark' : 'light' },
                    tooltip: { y: { formatter: value => 'S/ ' + Number(value || 0).toLocaleString('es-PE', { minimumFractionDigits: 2 }) } },
                }).render();
            },
            renderCharts() {
                this.area(this.$refs.yearChart, this.ventasAnio, 'label', 'Facturación');
                this.area(this.$refs.salesChart, this.ventas30, 'date', 'Ventas pagadas');
                if (this.$refs.ticketsChart && this.ticketsEstado.length) {
                    this.$refs.ticketsChart.innerHTML = '';
                    const isDark = document.documentElement.classList.contains('dark');
                    new window.ApexCharts(this.$refs.ticketsChart, {
                        chart: { type: 'donut', height: 245, foreColor: isDark ? '#cbd5e1' : '#475569' },
                        labels: this.ticketsEstado.map(row => row.estado || '—'),
                        series: this.ticketsEstado.map(row => Number(row.c || 0)),
                        legend: { position: 'bottom' },
                        colors: ['rgb(var(--gc-primary) / 1)', '#38bdf8', '#22c55e', '#f97316', '#ef4444', '#64748b'],
                        dataLabels: { enabled: false },
                        theme: { mode: isDark ? 'dark' : 'light' },
                    }).render();
                }
            }
        }"
        x-init="init(); window.addEventListener('gc-theme-changed', () => renderCharts())"
        class="space-y-5"
    >
        @if($isAdmin)
            <section class="overflow-hidden rounded-3xl bg-slate-950 text-white shadow-xl shadow-slate-200/70">
                <div class="grid gap-6 p-5 sm:p-6 xl:grid-cols-[1.45fr_.55fr]">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-emerald-400/15 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-300 ring-1 ring-inset ring-emerald-300/25">Panel ejecutivo</span>
                            <span class="text-xs capitalize text-slate-400">{{ $monthName }}</span>
                        </div>
                        <h2 class="mt-4 text-xl font-semibold sm:text-2xl">Lectura corporativa del negocio</h2>
                        <p class="mt-1 max-w-3xl text-sm text-slate-300">Facturación, liquidez y alertas operativas en una sola vista para priorizar decisiones comerciales.</p>
                        <div class="mt-5 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl bg-white/[0.07] p-4 ring-1 ring-inset ring-white/10">
                                <div class="text-[11px] uppercase tracking-wide text-slate-400">Facturación mensual</div>
                                <div class="mt-2 text-lg font-semibold">{{ $money($ventasMes) }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ (int) ($kpis['ventas_mes_count'] ?? 0) }} operaciones registradas</div>
                            </div>
                            <div class="rounded-2xl bg-white/[0.07] p-4 ring-1 ring-inset ring-white/10">
                                <div class="text-[11px] uppercase tracking-wide text-slate-400">Caja confirmada</div>
                                <div class="mt-2 text-lg font-semibold text-emerald-300">{{ $money($ventasPagadas) }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $avanceCaja }}% de la facturación mensual</div>
                            </div>
                            <div class="rounded-2xl bg-white/[0.07] p-4 ring-1 ring-inset ring-white/10">
                                <div class="text-[11px] uppercase tracking-wide text-slate-400">Facturación anual</div>
                                <div class="mt-2 text-lg font-semibold">{{ $money($ventasAnioTotal) }}</div>
                                <div class="mt-1 text-xs text-slate-400">Proyección: {{ $money($kpis['ventas_anio_proyeccion'] ?? 0) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl bg-white/[0.07] p-4 ring-1 ring-inset ring-white/10">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-300">Variación comercial</div>
                        <div class="mt-4 flex items-end justify-between gap-3">
                            <div>
                                <div class="text-xs text-slate-400">Mes anterior</div>
                                <div class="mt-1 text-lg font-semibold">{{ $money($ventasMesAnterior) }}</div>
                            </div>
                            @if($variacionMes !== null)
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $variacionMes >= 0 ? 'bg-emerald-400/15 text-emerald-300' : 'bg-rose-400/15 text-rose-300' }}">
                                    {{ $variacionMes >= 0 ? '↑' : '↓' }} {{ number_format(abs((float) $variacionMes), 1) }}%
                                </span>
                            @else
                                <span class="rounded-full bg-slate-700 px-2.5 py-1 text-xs font-semibold text-slate-300">Sin base previa</span>
                            @endif
                        </div>
                        <div class="mt-5">
                            <div class="flex justify-between text-xs text-slate-400"><span>Conversión a caja</span><span>{{ $avanceCaja }}%</span></div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-emerald-400" style="width: {{ $avanceCaja }}%"></div></div>
                        </div>
                        <div class="mt-5 grid grid-cols-2 gap-2 text-xs">
                            <a href="/ventas" class="rounded-xl bg-white/10 px-3 py-2.5 text-center font-semibold hover:bg-white/15">Ventas</a>
                            <a href="/reportes" class="rounded-xl bg-white/10 px-3 py-2.5 text-center font-semibold hover:bg-white/15">Reportes</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="gc-card bg-gradient-to-br from-sky-50 to-white p-4 dark:from-sky-950/50 dark:to-slate-900">
                    <div class="text-xs font-semibold text-sky-700">Ventas pendientes</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ (int) ($kpis['ventas_mes_pendientes_count'] ?? 0) }}</div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Operaciones por cobrar o completar.</div>
                </div>
                <div class="gc-card bg-gradient-to-br from-emerald-50 to-white p-4 dark:from-emerald-950/50 dark:to-slate-900">
                    <div class="text-xs font-semibold text-emerald-700">Comisiones pendientes</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $money($kpis['comisiones_pendientes_total'] ?? 0) }}</div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ (int) ($kpis['comisiones_pendientes_count'] ?? 0) }} registros por liquidar.</div>
                </div>
                <div class="gc-card bg-gradient-to-br from-amber-50 to-white p-4 dark:from-amber-950/50 dark:to-slate-900">
                    <div class="text-xs font-semibold text-amber-700">Stock crítico</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ (int) ($kpis['stock_bajo_count'] ?? 0) }}</div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Productos con inventario total ≤ 1.</div>
                </div>
                <div class="gc-card bg-gradient-to-br from-rose-50 to-white p-4 dark:from-rose-950/50 dark:to-slate-900">
                    <div class="text-xs font-semibold text-rose-700">Atención operativa</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $alertasOperativas }}</div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Tickets, agenda pendiente y stock crítico.</div>
                </div>
            </section>

            <section class="gc-card overflow-hidden">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                    <div>
                        <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Composición por documento</div>
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Comparativo de venta actual vs mes anterior según comprobante.</div>
                    </div>
                    <div class="flex flex-wrap gap-2 text-[11px] font-semibold">
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600 dark:bg-slate-800 dark:text-slate-300">Actual: {{ $docActual['periodo'] ?? now()->format('Y-m') }}</span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600 dark:bg-slate-800 dark:text-slate-300">Anterior: {{ $docAnterior['periodo'] ?? now()->subMonthNoOverflow()->format('Y-m') }}</span>
                    </div>
                </div>
                <div class="grid gap-4 p-5 lg:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">Factura / Boleta</div>
                                <div class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $money($docActualFactura['total'] ?? 0) }}</div>
                                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ (int) ($docActualFactura['count'] ?? 0) }} doc. · {{ number_format((float) ($docActualFactura['mix_pct'] ?? 0), 1) }}% del mes</div>
                            </div>
                            @if($facturaChange !== null)
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $facturaChange >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-400/10 dark:text-rose-300' }}">
                                    {{ $facturaChange >= 0 ? '↑' : '↓' }} {{ number_format(abs((float) $facturaChange), 1) }}%
                                </span>
                            @else
                                <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">Sin base</span>
                            @endif
                        </div>
                        <div class="mt-4 flex items-center justify-between rounded-xl bg-white px-3 py-2 text-xs dark:bg-slate-950">
                            <span class="text-slate-500 dark:text-slate-400">Mes anterior</span>
                            <strong class="text-slate-900 dark:text-slate-100">{{ $money($docAnteriorFactura['total'] ?? 0) }}</strong>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Nota de venta</div>
                                <div class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $money($docActualNota['total'] ?? 0) }}</div>
                                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ (int) ($docActualNota['count'] ?? 0) }} doc. · {{ number_format((float) ($docActualNota['mix_pct'] ?? 0), 1) }}% del mes</div>
                            </div>
                            @if($notaChange !== null)
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $notaChange >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-400/10 dark:text-rose-300' }}">
                                    {{ $notaChange >= 0 ? '↑' : '↓' }} {{ number_format(abs((float) $notaChange), 1) }}%
                                </span>
                            @else
                                <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">Sin base</span>
                            @endif
                        </div>
                        <div class="mt-4 flex items-center justify-between rounded-xl bg-white px-3 py-2 text-xs dark:bg-slate-950">
                            <span class="text-slate-500 dark:text-slate-400">Mes anterior</span>
                            <strong class="text-slate-900 dark:text-slate-100">{{ $money($docAnteriorNota['total'] ?? 0) }}</strong>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1.55fr_.75fr]">
                <div class="gc-card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Evolución anual de facturación</div>
                            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Ventas no anuladas, agrupadas por mes.</div>
                        </div>
                        <span class="rounded-full bg-primary/10 px-2.5 py-1 text-[11px] font-semibold text-primary">Año {{ now()->format('Y') }}</span>
                    </div>
                    <div class="mt-3" x-ref="yearChart"></div>
                </div>
                <aside class="gc-card p-5">
                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Interpretación financiera</div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Lectura automática sobre la información registrada.</div>
                    <div class="mt-4 space-y-3 text-xs leading-5 text-slate-600 dark:text-slate-300">
                        <p class="rounded-xl bg-slate-50 p-3 ring-1 ring-inset ring-slate-100 dark:bg-slate-950 dark:ring-slate-800">
                            @if($variacionMes === null)
                                No existe una base mensual previa suficiente para medir crecimiento. La prioridad es consolidar el registro continuo de ventas.
                            @elseif($variacionMes >= 0)
                                La facturación mensual crece <strong class="text-emerald-700">{{ number_format((float) $variacionMes, 1) }}%</strong> frente al mes anterior. Conviene identificar los productos que explican el avance y asegurar su reposición.
                            @else
                                La facturación mensual retrocede <strong class="text-rose-700">{{ number_format(abs((float) $variacionMes), 1) }}%</strong> frente al mes anterior. Se recomienda revisar conversión comercial, disponibilidad y seguimiento de pendientes.
                            @endif
                        </p>
                        <p class="rounded-xl bg-slate-50 p-3 ring-1 ring-inset ring-slate-100 dark:bg-slate-950 dark:ring-slate-800">
                            La caja confirmada representa <strong class="text-slate-900 dark:text-slate-100">{{ $avanceCaja }}%</strong> de la venta mensual y <strong class="text-slate-900 dark:text-slate-100">{{ $avanceCajaAnual }}%</strong> del acumulado anual.
                        </p>
                        <p class="rounded-xl bg-slate-50 p-3 ring-1 ring-inset ring-slate-100 dark:bg-slate-950 dark:ring-slate-800">
                            @if($bestProduct)
                                El producto líder es <strong class="text-slate-900 dark:text-slate-100">{{ $bestProduct['nombre'] ?? '—' }} {{ $bestProduct['modelo'] ?? '' }}</strong> con {{ (int) ($bestProduct['unidades'] ?? 0) }} unidades vendidas.
                            @else
                                Aún no existen ventas de productos suficientes para construir un ranking comercial.
                            @endif
                        </p>
                    </div>
                </aside>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1.55fr_.75fr]">
                <div class="gc-card overflow-hidden">
                    <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                        <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Desempeño de productos</div>
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Rotación acumulada del año {{ $rankings['productos_periodo'] ?? now()->format('Y') }}.</div>
                    </div>
                    <div class="grid md:grid-cols-2">
                        <div class="p-5 md:border-r md:border-slate-100 dark:md:border-slate-800">
                            <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Mayor rotación</div>
                            <div class="mt-3 space-y-2">
                                @forelse($masVendidos as $index => $product)
                                    <div class="flex items-center gap-3 rounded-xl bg-emerald-50/60 px-3 py-2.5 dark:bg-emerald-400/10">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-200">{{ $index + 1 }}</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-xs font-semibold text-slate-800 dark:text-slate-100">{{ $product['nombre'] ?? 'Producto' }} {{ $product['modelo'] ?? '' }}</div>
                                            <div class="truncate text-[11px] text-slate-500 dark:text-slate-400">{{ $product['sku'] ?? '—' }}</div>
                                        </div>
                                        <span class="text-xs font-semibold text-emerald-800 dark:text-emerald-200">{{ (int) ($product['unidades'] ?? 0) }} u.</span>
                                    </div>
                                @empty
                                    <div class="text-xs text-slate-500 dark:text-slate-400">Sin rotación registrada.</div>
                                @endforelse
                            </div>
                        </div>
                        <div class="p-5">
                            <div class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">Menor rotación</div>
                            <div class="mt-3 space-y-2">
                                @forelse($menosVendidos as $index => $product)
                                    <div class="flex items-center gap-3 rounded-xl bg-amber-50/70 px-3 py-2.5 dark:bg-amber-400/10">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-semibold text-amber-800 dark:bg-amber-400/15 dark:text-amber-200">{{ $index + 1 }}</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-xs font-semibold text-slate-800 dark:text-slate-100">{{ $product['nombre'] ?? 'Producto' }} {{ $product['modelo'] ?? '' }}</div>
                                            <div class="truncate text-[11px] text-slate-500 dark:text-slate-400">{{ $product['sku'] ?? '—' }}</div>
                                        </div>
                                        <span class="text-xs font-semibold text-amber-800 dark:text-amber-200">{{ (int) ($product['unidades'] ?? 0) }} u.</span>
                                    </div>
                                @empty
                                    <div class="text-xs text-slate-500 dark:text-slate-400">Sin rotación registrada.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                <aside class="gc-card p-5">
                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Control operativo</div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Pendientes que requieren seguimiento.</div>
                    <div class="mt-4 divide-y divide-slate-100 text-xs dark:divide-slate-800">
                        <a href="/tickets" class="flex items-center justify-between py-3 text-slate-600 hover:text-primary dark:text-slate-300"><span>Tickets abiertos</span><strong class="text-slate-900 dark:text-slate-100">{{ (int) ($kpis['tickets_abiertos_count'] ?? 0) }}</strong></a>
                        <a href="/agenda" class="flex items-center justify-between py-3 text-slate-600 hover:text-primary dark:text-slate-300"><span>Agenda pendiente</span><strong class="text-slate-900 dark:text-slate-100">{{ (int) ($kpis['agenda_pendientes_count'] ?? 0) }}</strong></a>
                        <a href="/agenda" class="flex items-center justify-between py-3 text-slate-600 hover:text-primary dark:text-slate-300"><span>Servicios de hoy</span><strong class="text-slate-900 dark:text-slate-100">{{ (int) ($kpis['agenda_hoy_count'] ?? 0) }}</strong></a>
                        <a href="/productos" class="flex items-center justify-between py-3 text-slate-600 hover:text-primary dark:text-slate-300"><span>Stock crítico</span><strong class="text-slate-900 dark:text-slate-100">{{ (int) ($kpis['stock_bajo_count'] ?? 0) }}</strong></a>
                    </div>
                </aside>
            </section>
        @else
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @if($can('agenda'))
                    <a href="/agenda" class="gc-card bg-gradient-to-br from-emerald-50 to-white p-5 dark:from-emerald-400/10 dark:to-slate-900">
                        <div class="text-xs font-semibold text-emerald-700">Agenda pendiente</div>
                        <div class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ (int) ($kpis['agenda_pendientes_count'] ?? 0) }}</div>
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Hoy: {{ (int) ($kpis['agenda_hoy_count'] ?? 0) }}</div>
                    </a>
                @endif
                @if($can('ventas'))
                    <a href="/ventas" class="gc-card bg-gradient-to-br from-primary/10 to-white p-5 dark:from-primary/15 dark:to-slate-900">
                        <div class="text-xs font-semibold text-primary">Ventas del mes</div>
                        <div class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $money($ventasMes) }}</div>
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ (int) ($kpis['ventas_mes_count'] ?? 0) }} operaciones visibles.</div>
                    </a>
                @endif
                @if($can('tickets'))
                    <a href="/tickets" class="gc-card bg-gradient-to-br from-sky-50 to-white p-5 dark:from-sky-400/10 dark:to-slate-900">
                        <div class="text-xs font-semibold text-sky-700">Tickets abiertos</div>
                        <div class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ (int) ($kpis['tickets_abiertos_count'] ?? 0) }}</div>
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Casos visibles por atender.</div>
                    </a>
                @endif
                @if($can('productos'))
                    <a href="/productos" class="gc-card bg-gradient-to-br from-amber-50 to-white p-5 dark:from-amber-400/10 dark:to-slate-900">
                        <div class="text-xs font-semibold text-amber-700">Stock crítico</div>
                        <div class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ (int) ($kpis['stock_bajo_count'] ?? 0) }}</div>
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Productos con stock total ≤ 1.</div>
                    </a>
                @endif
            </section>
            <section class="grid gap-5 lg:grid-cols-2">
                @if($can('ventas'))
                    <div class="gc-card p-5">
                        <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Ventas pagadas · 30 días</div>
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Evolución de tus operaciones visibles.</div>
                        <div class="mt-3" x-ref="salesChart"></div>
                    </div>
                @endif
                @if($can('tickets'))
                    <div class="gc-card p-5">
                        <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Tickets por estado</div>
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Distribución de tus casos visibles.</div>
                        <div class="mt-3" x-ref="ticketsChart"></div>
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-app-layout>
