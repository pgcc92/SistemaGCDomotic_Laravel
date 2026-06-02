<x-app-layout>
    <x-slot name="header">
        <div class="text-lg font-semibold text-slate-900">Comisiones</div>
    </x-slot>

    <div
        x-data="comisionesPage({
            urls: {
                data: '{{ route('comisiones.data') }}',
                ventasPeriodo: '{{ route('comisiones.ventas-periodo') }}',
                usuariosData: '{{ route('usuarios.data') }}',
                aprobar: (id) => `/comisiones/${id}/aprobar`,
                aprobarBulk: '{{ route('comisiones.aprobar-bulk') }}',
                pagar: '{{ route('comisiones.pagar') }}',
                liquidar: '{{ route('comisiones.liquidar') }}',
                export: '{{ route('comisiones.export') }}',
            }
        })"
        x-init="init()"
        class="space-y-6"
    >
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold text-slate-900">Top vendedores (vendido)</div>
                    <div class="text-xs text-slate-500">Periodo: <span class="font-medium" x-text="topPeriodoLabel"></span></div>
                </div>
                <div class="mt-3 space-y-2">
                    <template x-for="(r, idx) in topVendidos.slice(0,5)" :key="r.key">
                        <div class="flex items-center gap-3">
                            <div class="h-7 w-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xs font-bold" x-text="idx+1"></div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-slate-900 truncate" x-text="r.vendedor_nombre"></div>
                                <div class="text-[11px] text-slate-500" x-text="`${r.ventas_count} venta(s)`"></div>
                            </div>
                            <div class="text-sm font-semibold text-slate-900" x-text="money(r.total_vendido_pen)"></div>
                        </div>
                    </template>
                    <div class="text-sm text-slate-500 py-6 text-center" x-show="topVendidos.length===0">Sin datos.</div>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold text-slate-900">Top vendedores (comisión)</div>
                    <div class="text-xs text-slate-500">Periodo: <span class="font-medium" x-text="topPeriodoLabel"></span></div>
                </div>
                <div class="mt-3 space-y-2">
                    <template x-for="(r, idx) in topComision.slice(0,5)" :key="r.key">
                        <div class="flex items-center gap-3">
                            <div class="h-7 w-7 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xs font-bold" x-text="idx+1"></div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-slate-900 truncate" x-text="r.vendedor_nombre"></div>
                                <div class="text-[11px] text-slate-500" x-text="`${r.comisiones_count} comisión(es)`"></div>
                            </div>
                            <div class="text-sm font-semibold text-slate-900" x-text="money(r.importe_comision_pen)"></div>
                        </div>
                    </template>
                    <div class="text-sm text-slate-500 py-6 text-center" x-show="topComision.length===0">Sin datos.</div>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">Resumen del periodo</div>
                <div class="mt-1 text-xs text-slate-500">Visión rápida del mes en curso (según data cargada).</div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                        <div class="text-[11px] text-slate-500">Total vendido</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900" x-text="money(topSummary.total_vendido_pen)"></div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                        <div class="text-[11px] text-slate-500">Total comisión</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900" x-text="money(topSummary.total_comision_pen)"></div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3 col-span-2">
                        <div class="text-[11px] text-slate-500">Vendedores activos</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900" x-text="topSummary.vendedores_activos"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3a6 6 0 104.472 10.03l2.249 2.249a1 1 0 001.414-1.414l-2.249-2.249A6 6 0 009 3zm-4 6a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input x-model.debounce.200ms="q" @input="page=0"
                       placeholder="Buscar por venta, vendedor, estado o periodo…"
                       class="w-full rounded-xl border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-primary focus:ring-primary" />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50" @click="$dispatch('open-modal','comisiones-ventas-periodo'); resetPeriodo()">
                    Ventas por periodo
                </button>
                <a class="rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-white hover:bg-primary/90"
                   :href="exportUrl()"
                   target="_blank">
                    Export CSV
                </a>
            </div>
        </div>

        <x-table>
            <thead class="bg-slate-50/60">
                <tr class="text-left text-xs font-semibold text-slate-600">
                    <th class="px-4 py-3">Vendedor</th>
                    <th class="px-4 py-3">Periodo</th>
                    <th class="px-4 py-3 text-right">Total vendido</th>
                    <th class="px-4 py-3 text-right">Importe pagado</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Actualizado</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="row in pagedRows" :key="row.key">
                    <tr class="border-b border-slate-100 hover:bg-slate-50 cursor-pointer" @click="openPago(row)">
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-900" x-text="row.vendedor_nombre"></div>
                            <div class="mt-0.5 text-xs text-slate-500" x-text="`${row.ventas_count} venta(s) • ${row.comisiones_count} comisión(es)`"></div>
                        </td>
                        <td class="px-4 py-3 text-slate-700" x-text="row.periodo"></td>
                        <td class="px-4 py-3 text-right text-slate-900" x-text="money(row.total_vendido_pen)"></td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-900" x-text="money(row.importe_comision_pen)"></td>
                        <td class="px-4 py-3">
                            <template x-if="row.estado_ui === 'PENDIENTE'"><x-badge variant="amber">Pendiente</x-badge></template>
                            <template x-if="row.estado_ui === 'CANCELADO'"><x-badge variant="emerald">Cancelado</x-badge></template>
                        </td>
                        <td class="px-4 py-3 text-slate-600" x-text="fmtDate(row.updated_at)"></td>
                    </tr>
                </template>
                <tr x-show="!loading && filteredRows.length === 0">
                    <td class="px-4 py-10 text-center text-slate-500" colspan="6">No hay pagos de comisiones.</td>
                </tr>
            </tbody>
        </x-table>

        <x-pagination page="page" pages="pages"></x-pagination>

        <!-- Detalle de pago -->
        <x-modal name="comisiones-pago-detalle" maxWidth="6xl">
            <div class="border-b border-slate-200 px-6 py-4 flex items-start justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Pago de comisiones</div>
                    <div class="mt-0.5 text-xs text-slate-500" x-text="pagoSel ? `${pagoSel.vendedor_nombre} • ${pagoSel.periodo}` : '—'"></div>
                </div>
                <x-icon-button @click="$dispatch('close-modal','comisiones-pago-detalle')" aria-label="Cerrar">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </x-icon-button>
            </div>
            <div class="p-6 space-y-4">
                <template x-if="pagoError">
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="pagoError"></div>
                </template>
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="text-xs text-slate-500">Total vendido</div>
                        <div class="mt-1 text-lg font-semibold text-slate-900" x-text="money(pagoSel?.total_vendido_pen)"></div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="text-xs text-slate-500">Desc. instalador</div>
                        <div class="mt-1 text-lg font-semibold text-slate-900" x-text="money(pagoBreakdown?.instalador_fee_total_pen)"></div>
                        <div class="mt-0.5 text-[11px] text-slate-500">Total descontado en el periodo.</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="text-xs text-slate-500">Importe comisión</div>
                        <div class="mt-1 text-lg font-semibold text-slate-900" x-text="money(pagoSel?.importe_comision_pen)"></div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="text-xs text-slate-500">Estado</div>
                        <div class="mt-2">
                            <template x-if="pagoSel?.estado_ui === 'PENDIENTE'"><x-badge variant="amber">Pendiente</x-badge></template>
                            <template x-if="pagoSel?.estado_ui === 'CANCELADO'"><x-badge variant="emerald">Cancelado</x-badge></template>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">Desagregado</div>
                            <div class="mt-0.5 text-xs text-slate-500">Ventas PAGADAS del periodo (descuento instalador + base/IGV).</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="pagoSel?.estado_ui === 'CANCELADO'"
                                @click="$dispatch('open-modal','comisiones-ventas-periodo'); prefillPeriodoFromPago()">
                                Recalcular / pagar
                            </button>
                        </div>
                    </div>
                    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
                        <table class="min-w-[900px] w-full text-sm">
                            <thead class="bg-slate-50/70 text-xs font-semibold text-slate-600">
                                <tr class="text-left">
                                    <th class="px-3 py-2">Venta</th>
                                    <th class="px-3 py-2">Doc</th>
                                    <th class="px-3 py-2 text-right">Total</th>
                                    <th class="px-3 py-2 text-right">Desc. inst.</th>
                                    <th class="px-3 py-2 text-right">Base</th>
                                    <th class="px-3 py-2 text-right">IGV</th>
                                    <th class="px-3 py-2 text-right">%</th>
                                    <th class="px-3 py-2 text-right">Comisión</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="v in (pagoBreakdown.ventas || [])" :key="v.id">
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-3 py-2">
                                            <div class="font-medium text-slate-900" x-text="v.venta_codigo || ('#'+v.id)"></div>
                                            <div class="text-[11px] text-slate-500" x-text="fmtDate(v.fecha_venta)"></div>
                                        </td>
                                        <td class="px-3 py-2 text-slate-700" x-text="v.tipo_documento || '—'"></td>
                                        <td class="px-3 py-2 text-right text-slate-900" x-text="money(v.total_pen)"></td>
                                        <td class="px-3 py-2 text-right text-slate-700" x-text="money(v.instalador_fee_pen)"></td>
                                        <td class="px-3 py-2 text-right text-slate-900" x-text="money(v.base_calculo_pen)"></td>
                                        <td class="px-3 py-2 text-right text-slate-700" x-text="money(v.igv_pen)"></td>
                                        <td class="px-3 py-2 text-right text-slate-700" x-text="v.porcentaje ?? 0"></td>
                                        <td class="px-3 py-2 text-right font-semibold text-slate-900" x-text="money(v.monto_comision_pen)"></td>
                                    </tr>
                                </template>
                                <tr x-show="!pagoBreakdown.ventas || pagoBreakdown.ventas.length === 0">
                                    <td class="px-3 py-6 text-center text-slate-500" colspan="8">Sin detalle aún.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </x-modal>

        <!-- Ventas por periodo -->
        <x-modal name="comisiones-ventas-periodo" maxWidth="4xl">
            <form class="divide-y divide-slate-200" @submit.prevent="submitPeriodo()">
                <div class="px-6 py-4 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Ventas por periodo</div>
                        <div class="mt-0.5 text-xs text-slate-500">Calcula base y comisión en PEN.</div>
                    </div>
                    <x-icon-button @click="$dispatch('close-modal','comisiones-ventas-periodo')" aria-label="Cerrar">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </x-icon-button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <template x-if="periodoError">
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="periodoError"></div>
                    </template>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="text-xs font-medium text-slate-700">Periodo (YYYY-MM)</label>
                            <input x-model="periodo.periodo" placeholder="2026-05" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" required />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Descuento instalador (PEN)</label>
                            <input x-model="periodo.instalador_fee" type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" placeholder="Ej: 180" />
                            <div class="mt-1 text-[11px] text-slate-500">Se descuenta del total antes de calcular comisión.</div>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Porcentaje (opcional)</label>
                            <input x-model="periodo.porcentaje" type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" placeholder="Auto por regla si vacío" />
                            <div class="mt-1 text-[11px] text-slate-500">Si lo dejas vacío, usa la regla vigente del vendedor.</div>
                        </div>
                    </div>
                    <template x-if="periodoResult && (periodoResult.ventas || []).length">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="text-sm font-semibold text-slate-900">Aplicación del descuento</div>
                                    <div class="mt-0.5 text-xs text-slate-600">
                                        Marca qué documentos se <span class="font-semibold text-slate-900">incluyen</span> en el pago del periodo. Los desmarcados no suman comisión.
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs hover:bg-slate-50" @click="setApplyAll(true)">Aplicar a todos</button>
                                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs hover:bg-slate-50" @click="setApplyAll(false)">Quitar a todos</button>
                                    <button type="button" class="rounded-xl bg-primary px-3 py-2 text-xs font-semibold text-white hover:bg-primary/90" :disabled="loadingPeriodo" @click="recalcularConSeleccion()">
                                        <span x-text="loadingPeriodo ? 'Recalculando…' : 'Recalcular'"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3 text-xs">
                                    <div class="text-slate-500">Seleccionadas</div>
                                    <div class="mt-1 font-semibold text-slate-900" x-text="`${selectedVentaIds().length} / ${(periodoResult.ventas||[]).length}`"></div>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3 text-xs">
                                    <div class="text-slate-500">Descuento total aplicado</div>
                                    <div class="mt-1 font-semibold text-slate-900" x-text="money(periodoResult.instalador_fee_total_pen)"></div>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3 text-xs">
                                    <div class="text-slate-500">Base (PEN)</div>
                                    <div class="mt-1 font-semibold text-slate-900" x-text="money(periodoResult.base_pen)"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Vendedor (admin)</label>
                            <select x-model="periodo.vendedor_id" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                <option value="">(Todos / mi usuario)</option>
                                <template x-for="u in usuarios" :key="u.id">
                                    <option :value="String(u.id)" x-text="`${u.nombre ?? u.name ?? ('Usuario '+u.id)} (#${u.id})`"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <template x-if="periodoResult">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                                <div>
                                    <div class="text-xs text-slate-500">Saldo (PEN)</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="money(periodoResult.saldo_pen)"></div>
                                </div>
                                <div>
                                    <div class="text-xs text-slate-500">Base (PEN)</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="money(periodoResult.base_pen)"></div>
                                </div>
                                <div>
                                    <div class="text-xs text-slate-500">IGV (PEN)</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="money(periodoResult.igv_pen)"></div>
                                </div>
                                <div>
                                    <div class="text-xs text-slate-500">Comisión (PEN)</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="money(periodoResult.monto_comision_pen)"></div>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                                <x-badge variant="slate">Periodo: <span class="ml-1" x-text="periodoResult.periodo"></span></x-badge>
                                <x-badge variant="slate">Instalador: <span class="ml-1" x-text="money(periodoResult.instalador_fee_pen)"></span></x-badge>
                                <x-badge variant="slate">%: <span class="ml-1" x-text="`${periodoResult.porcentaje || 0}%`"></span></x-badge>
                                <span x-text="`${(periodoResult.ventas || []).length} venta(s) PAGADA(s).`"></span>
                            </div>
                            <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
                                <table class="min-w-[900px] w-full text-sm">
                                    <thead class="bg-slate-50/70 text-xs font-semibold text-slate-600">
                                        <tr class="text-left">
                                            <th class="px-3 py-2 w-10">
                                                <span class="sr-only">Aplicar</span>
                                            </th>
                                            <th class="px-3 py-2">Venta</th>
                                            <th class="px-3 py-2">Doc</th>
                                            <th class="px-3 py-2 text-right">Total</th>
                                            <th class="px-3 py-2 text-right">Saldo</th>
                                            <th class="px-3 py-2 text-right">Base</th>
                                            <th class="px-3 py-2 text-right">IGV</th>
                                            <th class="px-3 py-2 text-right">%</th>
                                            <th class="px-3 py-2 text-right">Comisión</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-for="v in (periodoResult.ventas || [])" :key="v.id">
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-3 py-2">
                                                    <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
                                                           :checked="!!v.apply_fee"
                                                           @change="v.apply_fee = $event.target.checked; recalcPeriodoLocal()" />
                                                </td>
                                                <td class="px-3 py-2">
                                                    <div class="font-medium text-slate-900" x-text="v.venta_codigo || ('#'+v.id)"></div>
                                                    <div class="text-[11px] text-slate-500" x-text="v.fecha_venta || '—'"></div>
                                                </td>
                                                <td class="px-3 py-2 text-slate-700" x-text="v.tipo_documento || '—'"></td>
                                                <td class="px-3 py-2 text-right text-slate-900" x-text="money(v.total_pen)"></td>
                                                <td class="px-3 py-2 text-right text-slate-900" x-text="money(v.saldo_pen)"></td>
                                                <td class="px-3 py-2 text-right text-slate-900" x-text="money(v.base_calculo_pen)"></td>
                                                <td class="px-3 py-2 text-right text-slate-700" x-text="money(v.igv_pen)"></td>
                                                <td class="px-3 py-2 text-right text-slate-700" x-text="(v.porcentaje ?? 0)"></td>
                                                <td class="px-3 py-2 text-right font-semibold text-slate-900" x-text="money(v.monto_comision_pen)"></td>
                                            </tr>
                                        </template>
                                        <tr x-show="(periodoResult.ventas || []).length === 0">
                                            <td class="px-3 py-6 text-center text-slate-500" colspan="9">No hay ventas pagadas en el periodo.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="px-6 py-4 flex items-center justify-end gap-2">
                    <div class="mr-auto flex items-center gap-2">
                        <label class="text-xs font-medium text-slate-600">Referencia (opcional)</label>
                        <input x-model="pagar.referencia" class="w-56 rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" placeholder="Ej: depósito / op." />
                    </div>
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50" @click="$dispatch('close-modal','comisiones-ventas-periodo')">Cerrar</button>
                    <button class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-900 ring-1 ring-inset ring-slate-200 hover:bg-slate-50"
                            :disabled="loadingPeriodo"
                            @click="submitPeriodo()">
                        <span x-text="loadingPeriodo ? 'Calculando…' : 'Calcular'"></span>
                    </button>
                    <button class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90 disabled:opacity-60"
                            :disabled="savingPagar || !periodo.periodo"
                            @click="liquidarDesdePeriodo()">
                        <span x-text="savingPagar ? 'Registrando pago…' : 'Generar pago'"></span>
                    </button>
                </div>
            </form>
        </x-modal>
    </div>

    <script>
        function comisionesPage({ urls }) {
            return {
                urls,
                q: '',
                rows: [], // raw comisiones
                pagos: [],
                loading: false,
                page: 0,
                perPage: 25,

                kpis: {
                    pendientes_count: 0, pendientes_total: 0,
                    aprobadas_count: 0, aprobadas_total: 0,
                    pagadas_count: 0, pagadas_total: 0,
                    total_visible: 0,
                },

                topPeriodoLabel: '—',
                topVendidos: [],
                topComision: [],
                topSummary: { total_vendido_pen: 0, total_comision_pen: 0, vendedores_activos: 0 },

                pagoSel: null,
                pagoError: '',
                pagoBreakdown: { ventas: [] },

                periodo: { periodo: '', instalador_fee: 180, porcentaje: '', vendedor_id: '' },
                periodoError: '',
                loadingPeriodo: false,
                periodoResult: null,

                usuarios: [],
                loadingUsuarios: false,

                pagar: { periodo: '', vendedor_id: '', referencia: '' },
                pagarError: '',
                pagarOk: '',
                savingPagar: false,

                async init() {
                    await Promise.all([this.reload(), this.loadUsuarios()]);
                },

                async loadUsuarios() {
                    this.loadingUsuarios = true;
                    try {
                        const res = await window.axios.get(this.urls.usuariosData, { headers: { 'Accept': 'application/json' } });
                        this.usuarios = res.data?.data || [];
                    } catch (e) {
                        this.usuarios = [];
                    } finally {
                        this.loadingUsuarios = false;
                    }
                },

                async reload() {
                    this.loading = true;
                    try {
                        const res = await window.axios.get(this.urls.data, { headers: { 'Accept': 'application/json' } });
                        this.rows = res.data?.data || [];
                        this.page = 0;
                        this.computeKpis();
                        this.computePagos();
                    } finally {
                        this.loading = false;
                    }
                },

                computeKpis() {
                    const k = {
                        pendientes_count: 0, pendientes_total: 0,
                        aprobadas_count: 0, aprobadas_total: 0,
                        pagadas_count: 0, pagadas_total: 0,
                        total_visible: 0,
                    };
                    for (const r of (this.rows || [])) {
                        const amt = Number(r.monto_pen ?? r.monto_comision ?? 0) || 0;
                        k.total_visible += amt;
                        const st = String(r.estado || '').toUpperCase();
                        if (st === 'PENDIENTE') { k.pendientes_count++; k.pendientes_total += amt; }
                        else if (st === 'APROBADA') { k.aprobadas_count++; k.aprobadas_total += amt; }
                        else if (st === 'PAGADA') { k.pagadas_count++; k.pagadas_total += amt; }
                    }
                    for (const key of Object.keys(k)) k[key] = typeof k[key] === 'number' ? Math.round(k[key] * 100) / 100 : k[key];
                    this.kpis = k;
                },

                computePagos() {
                    const map = new Map();
                    for (const r of (this.rows || [])) {
                        const periodo = String(r.periodo || '');
                        const vendedorId = Number(r.vendedor_id || 0);
                        if (!periodo || !vendedorId) continue;
                        const key = `${periodo}::${vendedorId}`;
                        if (!map.has(key)) {
                            map.set(key, {
                                key,
                                periodo,
                                vendedor_id: vendedorId,
                                vendedor_nombre: r.vendedor_nombre || this.usuarioNombre(vendedorId) || ('Vendedor #' + vendedorId),
                                ventas_ids: new Set(),
                                comisiones_ids: new Set(),
                                rows_count: 0,
                                paid_ok_count: 0,
                                total_vendido_pen: 0,
                                importe_comision_pen: 0,
                                estados: new Set(),
                                updated_at: r.updated_at || r.created_at || null,
                            });
                        }
                        const g = map.get(key);
                        g.rows_count++;
                        const ventaId = Number(r.venta_id || 0);
                        if (ventaId) g.ventas_ids.add(ventaId);
                        g.comisiones_ids.add(Number(r.id));
                        const vendido = Number(r.total_pen ?? r.total ?? 0) || 0;
                        g.total_vendido_pen += vendido;
                        const imp = Number(r.monto_pen ?? r.monto_comision ?? 0) || 0;
                        g.importe_comision_pen += imp;
                        const st = String(r.estado || '').toUpperCase();
                        g.estados.add(st);
                        if (st === 'PAGADA' && imp > 0) g.paid_ok_count++;
                        const upd = r.updated_at || r.created_at;
                        if (upd && (!g.updated_at || String(upd) > String(g.updated_at))) g.updated_at = upd;
                    }
                    const pagos = [];
                    for (const g of map.values()) {
                        // Solo consideramos "cancelado" cuando TODO el grupo está realmente pagado (PAGADA y monto > 0).
                        const allPaidEffective = (g.rows_count || 0) > 0 && (g.paid_ok_count || 0) === (g.rows_count || 0);
                        pagos.push({
                            key: g.key,
                            periodo: g.periodo,
                            vendedor_id: g.vendedor_id,
                            vendedor_nombre: g.vendedor_nombre,
                            ventas_count: g.ventas_ids.size,
                            comisiones_count: g.comisiones_ids.size,
                            total_vendido_pen: Math.round(g.total_vendido_pen * 100) / 100,
                            importe_comision_pen: Math.round(g.importe_comision_pen * 100) / 100,
                            estado_ui: allPaidEffective ? 'CANCELADO' : 'PENDIENTE',
                            updated_at: g.updated_at,
                        });
                    }
                    pagos.sort((a,b) => (a.periodo === b.periodo ? (b.importe_comision_pen - a.importe_comision_pen) : (a.periodo < b.periodo ? 1 : -1)));
                    this.pagos = pagos;
                    this.computeTop();
                },

                computeTop() {
                    const pagos = this.pagos || [];
                    const periodo = pagos.length ? pagos[0].periodo : '';
                    this.topPeriodoLabel = periodo || '—';
                    const same = periodo ? pagos.filter(p => p.periodo === periodo) : [];
                    this.topVendidos = [...same].sort((a,b) => b.total_vendido_pen - a.total_vendido_pen);
                    this.topComision = [...same].sort((a,b) => b.importe_comision_pen - a.importe_comision_pen);

                    let tv = 0, tc = 0;
                    for (const p of same) {
                        tv += Number(p.total_vendido_pen || 0);
                        tc += Number(p.importe_comision_pen || 0);
                    }
                    this.topSummary = {
                        total_vendido_pen: Math.round(tv * 100) / 100,
                        total_comision_pen: Math.round(tc * 100) / 100,
                        vendedores_activos: same.length,
                    };
                },

                get filteredRows() {
                    const q = (this.q || '').trim().toLowerCase();
                    if (!q) return this.pagos;
                    return this.pagos.filter(r =>
                        String(r.vendedor_nombre ?? '').toLowerCase().includes(q) ||
                        String(r.vendedor_id ?? '').toLowerCase().includes(q) ||
                        String(r.periodo ?? '').toLowerCase().includes(q) ||
                        String(r.estado_ui ?? '').toLowerCase().includes(q)
                    );
                },

                get pages() {
                    return Math.max(1, Math.ceil(this.filteredRows.length / this.perPage));
                },

                get pagedRows() {
                    const start = this.page * this.perPage;
                    return this.filteredRows.slice(start, start + this.perPage);
                },

                async openPago(row) {
                    this.pagoSel = row || null;
                    this.pagoError = '';
                    this.pagoBreakdown = { ventas: [] };
                    this.$dispatch('open-modal', 'comisiones-pago-detalle');
                    if (!row) return;
                    await this.loadPagoBreakdown(row.periodo, row.vendedor_id);
                },

                async loadPagoBreakdown(periodo, vendedorId) {
                    try {
                        // Mostrar el desagregado desde lo efectivamente pagado/registrado (tabla comisiones),
                        // no recalculando con parámetros que podrían diferir.
                        const q = { periodo: periodo, vendedor_id: Number(vendedorId) };
                        const res = await window.axios.get(this.urls.data, { params: q, headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.pagoError = res.data?.error || 'No se pudo cargar detalle.';
                            return;
                        }
                        const rows = res.data?.data || [];
                        const ventas = rows.map(r => {
                            const tipo = String(r.tipo_documento || '').toUpperCase();
                            const igvPct = Number(r.igv_porcentaje ?? 18) || 18;
                            const total = Number(r.total_pen ?? r.total ?? 0) || 0;
                            const base = Number(r.base_calculo ?? 0) || 0;
                            const pct = Number(r.porcentaje ?? 0) || 0;
                            const com = Number(r.monto_pen ?? r.monto_comision ?? 0) || 0;

                            let saldo = base;
                            let igv = 0;
                            if (tipo === 'FACTURA' || tipo === 'BOLETA') {
                                const div = 1 + Math.max(0, igvPct) / 100;
                                saldo = Math.round((base * div) * 100) / 100;
                                igv = Math.round(Math.max(0, saldo - base) * 100) / 100;
                            }
                            const fee = Math.round(Math.max(0, total - saldo) * 100) / 100;

                            return {
                                id: Number(r.venta_id || r.id),
                                venta_codigo: r.venta_codigo,
                                fecha_venta: r.fecha_venta,
                                tipo_documento: r.tipo_documento,
                                total_pen: Math.round(total * 100) / 100,
                                instalador_fee_pen: fee,
                                base_calculo_pen: Math.round(base * 100) / 100,
                                igv_pen: igv,
                                porcentaje: pct,
                                monto_comision_pen: Math.round(com * 100) / 100,
                            };
                        });

                        const feeTotal = Math.round(ventas.reduce((a, v) => a + (Number(v.instalador_fee_pen) || 0), 0) * 100) / 100;
                        this.pagoBreakdown = { ventas, instalador_fee_total_pen: feeTotal };
                    } catch (e) {
                        this.pagoError = e?.response?.data?.error || e?.message || 'Error';
                    }
                },

                prefillPeriodoFromPago() {
                    if (!this.pagoSel) return;
                    this.resetPeriodo();
                    this.periodo.periodo = this.pagoSel.periodo;
                    this.periodo.vendedor_id = String(this.pagoSel.vendedor_id);
                },

                resetPeriodo() {
                    this.periodoError = '';
                    this.periodoResult = null;
                    const now = new Date();
                    const y = now.getFullYear();
                    const m = String(now.getMonth() + 1).padStart(2, '0');
                    this.periodo = { periodo: `${y}-${m}`, instalador_fee: 180, porcentaje: '', vendedor_id: '' };
                },

                async submitPeriodo() {
                    this.periodoError = '';
                    this.loadingPeriodo = true;
                    this.periodoResult = null;
                    try {
                        const q = { periodo: this.periodo.periodo };
                        const pct = Number(this.periodo.porcentaje);
                        if (this.periodo.porcentaje !== '' && !Number.isNaN(pct) && pct > 0) q.porcentaje = pct;
                        const fee = Number(this.periodo.instalador_fee);
                        if (!Number.isNaN(fee) && fee >= 0) q.instalador_fee = fee;
                        if (this.periodo.vendedor_id) q.vendedor_id = Number(this.periodo.vendedor_id);

                        const res = await window.axios.get(this.urls.ventasPeriodo, { params: q, headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.periodoError = res.data?.error || 'No se pudo calcular.';
                            return;
                        }
                        this.periodoResult = res.data?.data || null;
                        this.hydrateApplyFee();
                    } catch (e) {
                        this.periodoError = e?.response?.data?.error || e?.message || 'Error';
                    } finally {
                        this.loadingPeriodo = false;
                    }
                },

                hydrateApplyFee() {
                    if (!this.periodoResult) return;
                    const ventas = this.periodoResult.ventas || [];
                    const ids = new Set((this.periodoResult.venta_ids || []).map(n => Number(n)));
                    for (const v of ventas) {
                        const id = Number(v.id);
                        // `venta_ids` ahora representa los documentos INCLUIDOS en el pago/cálculo.
                        v.apply_fee = ids.size ? ids.has(id) : (v.include !== false);
                        // Fee editable por documento (por defecto el fee calculado o el global actual)
                        const f = Number(v.instalador_fee_pen ?? this.periodo.instalador_fee ?? 0) || 0;
                        v.fee_input = Math.round(f * 100) / 100;
                    }
                    this.recalcPeriodoLocal();
                },

                selectedVentaIds() {
                    const ventas = this.periodoResult?.ventas || [];
                    return ventas.filter(v => !!v.apply_fee).map(v => Number(v.id)).filter(n => n > 0);
                },

                setApplyAll(on) {
                    const ventas = this.periodoResult?.ventas || [];
                    for (const v of ventas) v.apply_fee = !!on;
                    this.recalcPeriodoLocal();
                },

                async recalcularConSeleccion() {
                    if (!this.periodoResult) return;
                    this.periodoError = '';
                    this.loadingPeriodo = true;
                    try {
                        const q = { periodo: this.periodo.periodo };
                        const pct = Number(this.periodo.porcentaje);
                        if (this.periodo.porcentaje !== '' && !Number.isNaN(pct) && pct > 0) q.porcentaje = pct;
                        const fee = Number(this.periodo.instalador_fee);
                        if (!Number.isNaN(fee) && fee >= 0) q.instalador_fee = fee;
                        if (this.periodo.vendedor_id) q.vendedor_id = Number(this.periodo.vendedor_id);
                        const ids = this.selectedVentaIds();
                        q.venta_ids = ids.join(',');

                        const res = await window.axios.get(this.urls.ventasPeriodo, { params: q, headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.periodoError = res.data?.error || 'No se pudo recalcular.';
                            return;
                        }
                        this.periodoResult = res.data?.data || null;
                        this.hydrateApplyFee();
                    } catch (e) {
                        this.periodoError = e?.response?.data?.error || e?.message || 'Error';
                    } finally {
                        this.loadingPeriodo = false;
                    }
                },

                recalcPeriodoLocal() {
                    if (!this.periodoResult) return;
                    const ventas = this.periodoResult.ventas || [];
                    let saldo = 0, base = 0, igv = 0, com = 0, feeTotal = 0;
                    let totalVendido = 0;

                    for (const v of ventas) {
                        const total = Number(v.total_pen) || 0;
                        const pct = Number(v.porcentaje) || 0;
                        const igvPct = Number(v.igv_porcentaje ?? 18) || 18;
                        const tipo = String(v.tipo_documento || '').toUpperCase();
                        const apply = !!v.apply_fee;
                        if (!apply) {
                            v.instalador_fee_pen = 0;
                            v.saldo_pen = 0;
                            v.base_calculo_pen = 0;
                            v.igv_pen = 0;
                            v.monto_comision_pen = 0;
                            continue;
                        }

                        totalVendido += total;
                        const fee = Number(v.fee_input ?? 0) || 0;
                        const s = Math.max(0, total - fee);
                        let b = s;
                        let i = 0;
                        if (tipo === 'FACTURA' || tipo === 'BOLETA') {
                            const div = 1 + (Math.max(0, igvPct) / 100);
                            b = div > 0 ? (s / div) : s;
                            i = Math.max(0, s - b);
                        }
                        b = Math.round(b * 100) / 100;
                        i = Math.round(i * 100) / 100;
                        const m = pct > 0 ? Math.round((b * (pct / 100)) * 100) / 100 : 0;

                        v.instalador_fee_pen = Math.round(fee * 100) / 100;
                        v.saldo_pen = Math.round(s * 100) / 100;
                        v.base_calculo_pen = b;
                        v.igv_pen = i;
                        v.monto_comision_pen = m;

                        feeTotal += fee;
                        saldo += s;
                        base += b;
                        igv += i;
                        com += m;
                    }

                    this.periodoResult.instalador_fee_total_pen = Math.round(feeTotal * 100) / 100;
                    this.periodoResult.saldo_pen = Math.round(saldo * 100) / 100;
                    this.periodoResult.base_pen = Math.round(base * 100) / 100;
                    this.periodoResult.igv_pen = Math.round(igv * 100) / 100;
                    // si el porcentaje varía por vendedor/regla, usamos la suma por fila
                    this.periodoResult.monto_comision_pen = Math.round(com * 100) / 100;
                    this.periodoResult.total_vendido_pen = Math.round(totalVendido * 100) / 100;

                    // Sugerencia automática de % (editable): si supera 30,001 => 9%
                    const p = (this.periodo?.porcentaje ?? '');
                    if (String(p).trim() === '') {
                        const sug = (this.periodoResult.total_vendido_pen || 0) >= 30001 ? 9 : 7;
                        this.periodo.porcentaje = String(sug);
                    }
                },

                resetPagar() {
                    this.pagarError = '';
                    this.pagarOk = '';
                    const now = new Date();
                    const y = now.getFullYear();
                    const m = String(now.getMonth() + 1).padStart(2, '0');
                    this.pagar = { periodo: `${y}-${m}`, vendedor_id: '', referencia: '' };
                },

                async submitPagar() {
                    this.pagarError = '';
                    this.pagarOk = '';
                    this.savingPagar = true;
                    try {
                        const payload = {
                            periodo: this.pagar.periodo,
                            vendedor_id: this.pagar.vendedor_id ? Number(this.pagar.vendedor_id) : null,
                            referencia: this.pagar.referencia || null,
                        };
                        const res = await window.axios.post(this.urls.pagar, payload, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.pagarError = res.data?.error || 'No se pudo pagar.';
                            return;
                        }
                        const count = res.data?.data?.count;
                        this.pagarOk = `OK: ${count ?? 0} comisiones marcadas como PAGADA.`;
                        await this.reload();
                    } catch (e) {
                        this.pagarError = e?.response?.data?.error || e?.message || 'Error';
                    } finally {
                        this.savingPagar = false;
                    }
                },

                async pagarDesdePeriodo() {
                    this.pagarError = '';
                    this.pagarOk = '';
                    this.savingPagar = true;
                    try {
                        const ids = this.selectedVentaIds();
                        const payload = {
                            periodo: this.periodo.periodo,
                            vendedor_id: this.periodo.vendedor_id ? Number(this.periodo.vendedor_id) : null,
                            referencia: this.pagar.referencia || null,
                            venta_ids: ids.length ? ids.join(',') : null,
                        };
                        const res = await window.axios.post(this.urls.pagar, payload, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.pagarError = res.data?.error || 'No se pudo pagar.';
                            return;
                        }
                        const count = res.data?.data?.count;
                        const nSel = ids.length;
                        this.pagarOk = `OK: ${count ?? 0} comisión(es) marcadas como PAGADA${nSel ? ' (selección parcial)' : ''}.`;
                        window.GCToast?.success?.('Comisiones pagadas', this.pagarOk);
                        await this.reload();
                    } catch (e) {
                        this.pagarError = e?.response?.data?.error || e?.message || 'Error';
                    } finally {
                        this.savingPagar = false;
                    }
                },

                async liquidarDesdePeriodo() {
                    this.pagarError = '';
                    this.pagarOk = '';
                    this.savingPagar = true;
                    try {
                        const ids = this.selectedVentaIds();
                        if (!ids.length) {
                            this.pagarError = 'Debes seleccionar al menos un documento.';
                            return;
                        }
                        const payload = {
                            periodo: this.periodo.periodo,
                            vendedor_id: this.periodo.vendedor_id ? Number(this.periodo.vendedor_id) : null,
                            porcentaje: this.periodo.porcentaje !== '' ? Number(this.periodo.porcentaje) : null,
                            instalador_fee: this.periodo.instalador_fee !== '' ? Number(this.periodo.instalador_fee) : null,
                            referencia: this.pagar.referencia || null,
                            venta_ids: ids.join(','),
                            fees: Object.fromEntries((this.periodoResult?.ventas || [])
                                .filter(v => !!v.apply_fee)
                                .map(v => [String(v.id), Number(v.fee_input ?? v.instalador_fee_pen ?? this.periodo.instalador_fee ?? 0) || 0])),
                        };
                        const res = await window.axios.post(this.urls.liquidar, payload, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.pagarError = res.data?.error || 'No se pudo generar el pago.';
                            return;
                        }
                        const count = res.data?.data?.count;
                        this.pagarOk = `OK: ${count ?? 0} comisión(es) pagada(s) para la selección.`;
                        window.GCToast?.success?.('Pago generado', this.pagarOk);
                        await this.reload();
                    } catch (e) {
                        this.pagarError = e?.response?.data?.error || e?.message || 'Error';
                    } finally {
                        this.savingPagar = false;
                    }
                },

                exportUrl() {
                    // opcional: exportar por periodo si está seteado en pagar.periodo
                    const p = (this.pagar?.periodo || '').trim();
                    if (!p) return this.urls.export;
                    const u = new URL(this.urls.export, window.location.origin);
                    u.searchParams.set('periodo', p);
                    return u.pathname + u.search;
                },

                money(v) {
                    const n = (v === null || v === undefined) ? null : Number(v);
                    if (n === null || Number.isNaN(n)) return '—';
                    return n.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                usuarioNombre(id) {
                    const n = Number(id);
                    if (!n) return '';
                    const u = (this.usuarios || []).find(x => Number(x.id) === n);
                    return u ? (u.nombre ?? u.name ?? '') : '';
                },

                fmtDate(v) {
                    if (!v) return '—';
                    const s = String(v);
                    // admite "YYYY-MM-DD HH:MM:SS" o ISO; tomamos componentes rápido
                    const m = s.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
                    if (m) return `${m[3]}/${m[2]}/${m[1]} ${m[4]}:${m[5]}`;
                    return s;
                },
            }
        }
    </script>
</x-app-layout>
