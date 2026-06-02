<x-app-layout>
    <x-slot name="header">
        @php
            $branding = app(\App\Domain\Tenant\TenantContext::class)->branding();
        @endphp
        <div class="flex flex-col gap-1">
            <div class="text-lg font-semibold text-slate-900">Reportes</div>
            <div class="text-sm text-slate-500">Genera reportes en PDF listos para imprimir.</div>
        </div>
</x-slot>

<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div></div>

        <div class="flex items-center gap-2">
            <button type="button"
                class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:opacity-95"
                @click="document.getElementById('gc-report-generator')?.scrollIntoView({ behavior: 'smooth', block: 'start' })">
                Generar reporte
            </button>
        </div>
    </div>

    @php
        $k = $dashboard['kpis'] ?? [];
    @endphp
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="text-xs text-slate-500">Ventas del mes</div>
            <div class="mt-1 text-xl font-semibold text-slate-900">{{ number_format((float)($k['ventas_mes_total'] ?? 0), 2) }}</div>
            <div class="mt-1 text-xs text-slate-500">{{ (int)($k['ventas_mes_count'] ?? 0) }} venta(s)</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="text-xs text-slate-500">Comisiones pendientes</div>
            <div class="mt-1 text-xl font-semibold text-slate-900">{{ number_format((float)($k['comisiones_pendientes_total'] ?? 0), 2) }}</div>
            <div class="mt-1 text-xs text-slate-500">{{ (int)($k['comisiones_pendientes_count'] ?? 0) }} comisión(es)</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="text-xs text-slate-500">Tickets abiertos</div>
            <div class="mt-1 text-xl font-semibold text-slate-900">{{ (int)($k['tickets_abiertos_count'] ?? 0) }}</div>
            <div class="mt-1 text-xs text-slate-500">En curso</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="text-xs text-slate-500">Stock bajo</div>
            <div class="mt-1 text-xl font-semibold text-slate-900">{{ (int)($k['stock_bajo_count'] ?? 0) }}</div>
            <div class="mt-1 text-xs text-slate-500">Productos con stock ≤ 1</div>
        </div>
    </div>

    <div id="gc-report-generator"
        class="rounded-2xl border border-slate-200 bg-white p-5"
        x-data="{
            format: 'a4',
            type: 'resumen',
            periodo: '{{ now()->format('Y-m') }}',
            from: '{{ now()->startOfMonth()->toDateString() }}',
            to: '{{ now()->endOfMonth()->toDateString() }}',
            reports: [
                { key: 'resumen', title: 'Resumen general', desc: 'KPIs de ventas, comisiones, tickets, stock y agenda.' },
                { key: 'productos_lista', title: 'Productos (lista)', desc: 'SKU, nombre, modelo, precio y stock total.' },
                { key: 'productos_kardex', title: 'Kardex / movimientos', desc: 'Movimientos del inventario (periodo actual).' },
                { key: 'productos_detalle', title: 'Productos (detalle)', desc: 'Productos + movimientos por producto (A4 recomendado).' },
                { key: 'ventas_detalle', title: 'Ventas (detalle)', desc: 'Listado de ventas por rango de fechas.' },
                { key: 'tickets_resumen', title: 'Tickets (resumen)', desc: 'Resumen por estado + últimos tickets.' },
            ],
        }">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-sm font-semibold text-slate-900">Generador de PDF</div>
                <p class="mt-1 text-sm text-slate-500">Elige el reporte y formato. Se abrirá en una pestaña nueva (no descarga automática).</p>
            </div>
            <a class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:opacity-95"
                target="_blank" rel="noopener"
                :href="(() => {
                    const u = new URL('{{ route('reportes.pdf') }}');
                    u.searchParams.set('format', format);
                    u.searchParams.set('type', type);
                    if (type === 'ventas_detalle') { u.searchParams.set('from', from); u.searchParams.set('to', to); }
                    return u.toString();
                })()">
                Abrir PDF
            </a>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-4">
            <div>
                <label class="text-xs font-medium text-slate-700">Reporte</label>
                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <template x-for="r in reports" :key="r.key">
                        <label class="group relative cursor-pointer rounded-2xl border border-slate-200 bg-white p-4 transition hover:bg-slate-50"
                            :class="type === r.key ? 'bg-primary/5 ring-2 ring-primary/30 border-primary/50 shadow-sm' : ''">
                            <input type="radio" class="sr-only" name="type" :value="r.key" x-model="type">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-slate-900" x-text="r.title"></div>
                                    <div class="mt-1 text-xs text-slate-500" x-text="r.desc"></div>
                                    <div class="mt-3 text-[11px] text-slate-500">Tipo: <span class="font-mono" x-text="r.key"></span></div>
                                </div>
                                <div class="shrink-0 pt-0.5" x-show="type === r.key" aria-hidden="true">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary text-white shadow pointer-events-none">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </label>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <label class="relative cursor-pointer rounded-2xl border border-slate-200 bg-white p-4 transition hover:bg-slate-50"
                   :class="format === 'a4' ? 'bg-primary/5 ring-2 ring-primary/30 border-primary/50 shadow-sm' : ''">
                <input type="radio" class="sr-only" name="format" value="a4" x-model="format">
                <div class="absolute right-3 top-3" x-show="format === 'a4'">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                </div>
                <div class="text-sm font-semibold text-slate-900">A4</div>
                <div class="mt-1 text-xs text-slate-500">210×297mm</div>
            </label>
            <label class="relative cursor-pointer rounded-2xl border border-slate-200 bg-white p-4 transition hover:bg-slate-50"
                   :class="format === 'ticket80' ? 'bg-primary/5 ring-2 ring-primary/30 border-primary/50 shadow-sm' : ''">
                <input type="radio" class="sr-only" name="format" value="ticket80" x-model="format">
                <div class="absolute right-3 top-3" x-show="format === 'ticket80'">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                </div>
                <div class="text-sm font-semibold text-slate-900">Ticket 80mm</div>
                <div class="mt-1 text-xs text-slate-500">80mm (rollo)</div>
            </label>
            <label class="relative cursor-pointer rounded-2xl border border-slate-200 bg-white p-4 transition hover:bg-slate-50"
                   :class="format === 'ticket58' ? 'bg-primary/5 ring-2 ring-primary/30 border-primary/50 shadow-sm' : ''">
                <input type="radio" class="sr-only" name="format" value="ticket58" x-model="format">
                <div class="absolute right-3 top-3" x-show="format === 'ticket58'">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                </div>
                <div class="text-sm font-semibold text-slate-900">Ticket 58mm</div>
                <div class="mt-1 text-xs text-slate-500">58mm (rollo)</div>
            </label>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-slate-500">Seleccionado:</span>
                <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-800 border border-slate-200">
                    <span class="font-mono" x-text="type"></span>
                </span>
                <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-800 border border-slate-200">
                    <span x-text="format === 'a4' ? 'A4' : (format === 'ticket80' ? 'Ticket 80mm' : 'Ticket 58mm')"></span>
                </span>
            </div>
        </div>

        <div x-show="type === 'ventas_detalle'" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="text-xs font-medium text-slate-700">Desde</label>
                <input type="date" x-model="from" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
            </div>
            <div>
                <label class="text-xs font-medium text-slate-700">Hasta</label>
                <input type="date" x-model="to" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-2">
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">
                <span class="text-slate-500">Seleccionado:</span>
                <span class="font-mono" x-text="type"></span>
                <span class="text-slate-400">•</span>
                <span x-text="format === 'a4' ? 'A4' : (format === 'ticket80' ? 'Ticket 80mm' : 'Ticket 58mm')"></span>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
