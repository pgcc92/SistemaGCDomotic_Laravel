<x-app-layout>
    <x-slot name="header">
        <div class="text-lg font-semibold text-slate-900">Ventas</div>
    </x-slot>

    <div
	        x-data="ventasPage({
	            urls: {
	                data: '{{ route('ventas.data') }}',
	                show: (id) => `/ventas/${id}`,
	                create: '{{ route('ventas.store') }}',
	                pagar: (id) => `/ventas/${id}/pagar`,
	                anular: (id) => `/ventas/${id}/anular`,
	                stats: '{{ route('ventas.stats') }}',
	                next: '{{ route('ventas.next-correlativo') }}',
	                productos: '{{ route('productos.data') }}',
	                clientes: '{{ route('clientes.data') }}',
	                usuarios: '{{ route('usuarios.data') }}',
	                sucursales: '{{ route('sucursales.data') }}',
	            }
	        })"
        x-init="init()"
        class="space-y-6"
    >
        <div class="grid gap-4 lg:grid-cols-4">
            <div class="gc-card p-5 bg-gradient-to-br from-emerald-50 to-white">
                <div class="text-xs font-medium text-emerald-700/80">Pagadas (mes)</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.pagadasMes"></div>
            </div>
            <div class="gc-card p-5 bg-gradient-to-br from-amber-50 to-white">
                <div class="text-xs font-medium text-amber-700/80">Pendientes (mes)</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.pendientes"></div>
            </div>
            <div class="gc-card p-5 bg-gradient-to-br from-primary/10 to-white">
                <div class="text-xs font-medium text-primary/80">Total (mes)</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900">
                    <span class="text-slate-500 text-sm font-semibold" x-text="kpis.moneda"></span>
                    <span class="ms-1" x-text="kpis.totalMes"></span>
                </div>
            </div>
            <div class="gc-card p-5 bg-gradient-to-br from-sky-50 to-white">
                <div class="text-xs font-medium text-sky-700/80">Moneda</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.moneda"></div>
            </div>
        </div>

	        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
	            <div class="relative w-full sm:max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3a6 6 0 104.472 10.03l2.249 2.249a1 1 0 001.414-1.414l-2.249-2.249A6 6 0 009 3zm-4 6a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input x-model.debounce.200ms="q" @input="applySearch()"
                       placeholder="Buscar por venta, cliente, documento o teléfono…"
                       class="w-full rounded-xl border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-primary focus:ring-primary" />
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-white hover:bg-primary/90"
                        @click="$dispatch('open-modal','venta-form'); $dispatch('venta-new')">
                    Nueva venta
                </button>
	            </div>
	        </div>

	        <div class="gc-card p-5">
	            <x-table class="border-0 shadow-none">
                <thead class="bg-slate-50/60">
                    <tr class="text-left text-xs font-semibold text-slate-600">
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Documento</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in pagedRows" :key="row.id">
                        <tr class="border-b border-slate-100 hover:bg-slate-50 cursor-pointer"
                            @click="openDetail(row.id)">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900" x-text="row.venta_codigo"></div>
                                <div class="mt-1 text-xs text-slate-500" x-text="shortDateTime(row.fecha_venta)"></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-semibold text-slate-900" x-text="clienteVenta(row)"></div>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <template x-if="clienteVentaDocumento(row)">
                                        <span class="inline-flex items-center rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-semibold text-sky-700 ring-1 ring-inset ring-sky-200"
                                              x-text="clienteVentaDocumento(row)"></span>
                                    </template>
                                    <template x-if="clienteVentaTelefono(row)">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200"
                                              x-text="clienteVentaTelefono(row)"></span>
                                    </template>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-slate-900 font-medium" x-text="row.tipo_documento"></div>
                                <div class="text-xs text-slate-500" x-text="docLabel(row)"></div>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-900" x-text="money(row.total, row.moneda)"></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                                      :class="estadoClass(row.estado)"
                                      x-text="row.estado || '—'"></span>
                            </td>
                            <td class="px-4 py-3 text-slate-600" x-text="shortDateTime(row.fecha_venta)"></td>
                        </tr>
                    </template>
                    <tr x-show="!loading && filteredRows.length === 0">
                        <td class="px-4 py-10 text-center text-slate-500" colspan="6">No hay ventas.</td>
                    </tr>
                </tbody>
            </x-table>
        </div>

        <x-pagination page="page" pages="pages"></x-pagination>

        <!-- Detalle -->
        <x-modal name="venta-detalle" maxWidth="4xl">
            <div class="border-b border-slate-200 px-6 py-4 bg-white/80 backdrop-blur">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="text-sm font-semibold text-slate-900 truncate" x-text="detail?.venta_codigo ? `Venta ${detail.venta_codigo}` : 'Venta'"></div>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                  :class="estadoClass(detail?.estado)"
                                  x-text="detail?.estado || '—'"></span>
                        </div>
                        <div class="mt-0.5 text-xs text-slate-500">
                            <span x-text="detail?.tipo_documento || ''"></span>
                            <template x-if="detail?.serie_documento || detail?.numero_documento">
                                <span class="ms-1" x-text="`${detail?.serie_documento || ''}-${detail?.numero_documento || ''}`"></span>
                            </template>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <template x-if="String(detail?.estado || '').toUpperCase() === 'PENDIENTE'">
                            <button type="button"
                                    class="rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-white hover:bg-primary/90"
                                    @click="$dispatch('open-modal','venta-pagar')">
                                Pagar
                            </button>
                        </template>
                        <button type="button"
                                class="rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700"
                                @click="$dispatch('open-modal','venta-anular')">
                            Anular
                        </button>
                        <x-icon-button @click="$dispatch('close-modal','venta-detalle')" aria-label="Cerrar">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </x-icon-button>
                    </div>
                </div>
            </div>
            <div class="p-6 space-y-6">
                <template x-if="detailError">
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="detailError"></div>
                </template>

                <div class="grid gap-6 lg:grid-cols-12">
                    <div class="lg:col-span-4 space-y-4">
                        <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-sky-50/70 to-white p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-xs font-semibold text-slate-900">Cliente</div>
                                <span class="inline-flex items-center rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200"
                                      x-text="detailClienteBadge()"></span>
                            </div>
                            <div class="mt-3 text-sm font-semibold text-slate-900" x-text="detailClienteNombre()"></div>
                            <dl class="mt-3 space-y-2 text-sm">
                                <div class="flex justify-between gap-3"><dt class="text-slate-500">Teléfono</dt><dd class="text-slate-900" x-text="detailClienteTelefono() || '—'"></dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-slate-500">Documento</dt><dd class="text-slate-900" x-text="detailClienteDocumento() || '—'"></dd></div>
                                <div class="grid grid-cols-1 gap-1"><dt class="text-slate-500">Dirección</dt><dd class="text-slate-900" x-text="detailClienteDireccion() || '—'"></dd></div>
                                <div class="grid grid-cols-1 gap-1"><dt class="text-slate-500">Email</dt><dd class="text-slate-900" x-text="detailClienteEmail() || '—'"></dd></div>
                            </dl>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="text-xs font-semibold text-slate-900">Resumen</div>
                            <dl class="mt-3 space-y-2 text-sm">
                                <div class="flex justify-between gap-3"><dt class="text-slate-500">Total</dt><dd class="text-slate-900 font-semibold" x-text="money(detail?.total, detail?.moneda)"></dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-slate-500">Fecha</dt><dd class="text-slate-900" x-text="shortDateTime(detail?.fecha_venta)"></dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-slate-500">Sucursal</dt><dd class="text-slate-900" x-text="sucursalLabel(detail?.sucursal_id)"></dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-slate-500">Vendedor</dt><dd class="text-slate-900" x-text="usuarioLabel(detail?.vendedor_id)"></dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-slate-500">Pago</dt><dd class="text-slate-900" x-text="detail?.metodo_pago || '—'"></dd></div>
                            </dl>
                            <template x-if="detail?.notas">
                                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50/60 p-3 text-xs text-slate-700 whitespace-pre-line" x-text="detail?.notas"></div>
                            </template>
                        </div>
                    </div>

                    <div class="lg:col-span-8 space-y-4">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="text-xs font-semibold text-slate-900">Ítems</div>
                            <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200">
                                <div class="w-full overflow-x-auto">
                                    <table class="min-w-[640px] w-full divide-y divide-slate-200">
                                        <thead class="bg-slate-50">
                                            <tr class="text-left text-[11px] font-semibold tracking-wide text-slate-600">
                                                <th class="px-3 py-2">Descripción</th>
                                                <th class="px-3 py-2 text-right">Cant</th>
                                                <th class="px-3 py-2 text-right whitespace-nowrap">P.U.</th>
                                                <th class="px-3 py-2 text-right">Desc</th>
                                                <th class="px-3 py-2 text-right">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            <template x-for="it in (detailItems || [])" :key="it.id">
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-3 py-2 text-sm text-slate-900" x-text="it.descripcion"></td>
                                                    <td class="px-3 py-2 text-right text-sm text-slate-700" x-text="it.cantidad"></td>
                                                    <td class="px-3 py-2 text-right text-sm text-slate-700" x-text="money(it.precio_unit, detail?.moneda)"></td>
                                                    <td class="px-3 py-2 text-right text-sm text-slate-700" x-text="money(it.descuento, detail?.moneda)"></td>
                                                    <td class="px-3 py-2 text-right text-sm font-semibold text-slate-900" x-text="money(it.total, detail?.moneda)"></td>
                                                </tr>
                                            </template>
                                            <tr x-show="(detailItems || []).length === 0">
                                                <td colspan="5" class="px-3 py-10 text-center text-sm text-slate-500">Sin ítems.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-modal>

        <!-- Crear -->
	        <x-modal name="venta-form" maxWidth="full" focusable>
	            <form class="divide-y divide-slate-200" @submit.prevent="submitForm()">
	                <div class="px-6 py-4 flex items-start justify-between gap-3">
	                    <div>
	                        <div class="text-sm font-semibold text-slate-900">Nueva venta</div>
	                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-600">
	                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">POS</span>
	                            <span>Precios incluyen IGV</span>
	                            <span class="text-slate-400">•</span>
	                            <span>Desagregado SUNAT para Factura/Boleta</span>
	                        </div>
	                    </div>
	                    <x-icon-button @click="$dispatch('close-modal','venta-form')" aria-label="Cerrar">
	                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
	                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
	                        </svg>
	                    </x-icon-button>
	                </div>
                <div class="px-6 py-5">
                    <!-- Errores se muestran como toast (SaaS) -->

	                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start">
	                        <!-- Items / POS -->
	                        <div class="space-y-5 min-w-0">
	                            <div class="rounded-2xl border border-slate-200 bg-white p-5">
	                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
	                                    <div class="flex items-center gap-2">
	                                        <div class="text-xs font-semibold text-slate-900">Items</div>
	                                        <template x-if="items.length > 0">
	                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200" x-text="items.length + ' ítems'"></span>
	                                        </template>
	                                    </div>
	                                    <div class="flex items-center gap-2">
	                                        <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50"
	                                                @click="addServicio()">
	                                            + Servicio
	                                        </button>
	                                    </div>
	                                </div>

	                                <div class="mt-4 relative">
	                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
	                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
	                                            <path fill-rule="evenodd" d="M9 3a6 6 0 104.472 10.03l2.249 2.249a1 1 0 001.414-1.414l-2.249-2.249A6 6 0 009 3zm-4 6a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
	                                        </svg>
	                                    </div>
	                                    <input x-model.debounce.250ms="prodQ" @input="searchProductos()" @focus="prodOpen=true" @keydown.escape="prodOpen=false"
	                                           placeholder="Buscar producto por SKU, nombre o modelo…"
	                                           class="w-full rounded-xl border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-primary focus:ring-primary" />

	                                    <div x-show="prodOpen && prodQ.trim().length > 0" x-transition.opacity class="absolute z-20 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
	                                        <div class="max-h-64 overflow-auto">
	                                            <template x-for="p in prodResults" :key="p.id">
	                                                <button type="button" class="w-full text-left px-4 py-3 hover:bg-slate-50 flex items-center justify-between gap-3"
	                                                        @click="addProducto(p); prodQ=''; prodOpen=false">
	                                                    <div class="min-w-0">
	                                                        <div class="text-sm font-medium text-slate-900 truncate" x-text="p.nombre"></div>
	                                                        <div class="mt-0.5 text-xs text-slate-500 truncate">
	                                                            <span x-text="p.sku || ''"></span>
	                                                            <template x-if="p.modelo">
	                                                                <span class="ml-2" x-text="p.modelo"></span>
	                                                            </template>
	                                                        </div>
	                                                    </div>
	                                                    <div class="text-sm font-semibold text-slate-900 whitespace-nowrap" x-text="money(p.precio, p.moneda)"></div>
	                                                </button>
	                                            </template>
	                                            <div class="px-4 py-6 text-sm text-slate-500" x-show="prodLoading">Buscando…</div>
	                                            <div class="px-4 py-6 text-sm text-slate-500" x-show="!prodLoading && prodResults.length === 0">Sin resultados.</div>
	                                        </div>
	                                    </div>
	                                </div>

		                                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
		                                    <div class="max-h-[45vh] overflow-y-auto overflow-x-hidden">
		                                        <!-- Mobile: cards -->
		                                        <div class="sm:hidden divide-y divide-slate-100 bg-white">
		                                            <template x-for="(it, idx) in items" :key="it._k">
		                                                <div class="p-3 space-y-3">
		                                                    <div class="flex items-start justify-between gap-3">
		                                                        <div class="min-w-0">
		                                                            <input x-model="it.descripcion"
		                                                                   class="w-full min-w-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-900 focus:border-primary focus:ring-primary"
		                                                                   placeholder="Descripción" />
		                                                            <div class="mt-1 text-xs text-slate-500" x-text="it.sku || ''"></div>
		                                                        </div>
		                                                        <button type="button" class="rounded-lg p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50"
		                                                                @click="removeItem(idx)" aria-label="Quitar">
		                                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
		                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
		                                                            </svg>
		                                                        </button>
		                                                    </div>
		                                                    <div class="grid grid-cols-2 gap-3">
		                                                        <div>
		                                                            <div class="text-[11px] font-semibold text-slate-600">P.U. (incl. IGV)</div>
		                                                            <input type="number" step="0.01" min="0" x-model.number="it.precio_unit"
		                                                                   class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
		                                                        </div>
		                                                        <div>
		                                                            <div class="text-[11px] font-semibold text-slate-600">Desc</div>
		                                                            <input type="number" step="0.01" min="0" x-model.number="it.descuento"
		                                                                   class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
		                                                        </div>
		                                                    </div>
		                                                    <div class="flex items-center justify-between gap-3">
		                                                        <div class="inline-flex items-center overflow-hidden rounded-xl border border-slate-200 bg-white">
		                                                            <button type="button" class="px-3 py-2 text-slate-600 hover:text-slate-900" @click="decQty(idx)">−</button>
		                                                            <input type="number" min="1" step="1" x-model.number="it.cantidad"
		                                                                   class="w-14 text-center border-0 focus:ring-0 text-sm" />
		                                                            <button type="button" class="px-3 py-2 text-slate-600 hover:text-slate-900" @click="incQty(idx)">+</button>
		                                                        </div>
		                                                        <div class="text-sm font-semibold text-slate-900 whitespace-nowrap" x-text="money(itemTotal(it), form.moneda)"></div>
		                                                    </div>
		                                                </div>
		                                            </template>
		                                            <div class="px-3 py-10 text-center text-sm text-slate-500" x-show="items.length === 0">Agrega productos o servicios.</div>
		                                        </div>

		                                        <!-- Desktop/tablet: table -->
		                                        <div class="hidden sm:block w-full overflow-x-auto">
		                                            <table class="min-w-[900px] w-full table-fixed divide-y divide-slate-200">
		                                                <colgroup>
		                                                    <col class="w-[46%]" />
		                                                    <col class="w-[16%]" />
		                                                    <col class="w-[16%]" />
		                                                    <col class="w-[12%]" />
		                                                    <col class="w-[10%]" />
		                                                    <col class="w-[2%]" />
		                                                </colgroup>
		                                                <thead class="bg-slate-50">
		                                                    <tr class="text-left text-[11px] font-semibold tracking-wide text-slate-600">
		                                                        <th class="px-3 py-2">Producto</th>
		                                                        <th class="px-3 py-2 text-right whitespace-nowrap">P.U. (incl. IGV)</th>
		                                                        <th class="px-3 py-2 text-right whitespace-nowrap">Cant</th>
		                                                        <th class="px-3 py-2 text-right whitespace-nowrap">Desc</th>
		                                                        <th class="px-3 py-2 text-right whitespace-nowrap">Total</th>
		                                                        <th class="px-3 py-2"></th>
		                                                    </tr>
		                                                </thead>
		                                                <tbody class="divide-y divide-slate-100 bg-white">
		                                            <template x-for="(it, idx) in items" :key="it._k">
		                                                <tr class="hover:bg-slate-50 align-top">
		                                                    <td class="px-3 py-2 min-w-0">
		                                                        <input x-model="it.descripcion"
		                                                               class="w-full min-w-0 truncate rounded-lg border border-transparent bg-transparent px-1 py-0.5 text-sm font-medium text-slate-900 focus:border-slate-200 focus:bg-white focus:ring-primary"
		                                                               placeholder="Descripción" />
		                                                        <div class="mt-0.5 text-xs text-slate-500" x-text="it.sku || ''"></div>
		                                                    </td>
		                                                    <td class="px-3 py-2 text-right">
		                                                        <input type="number" step="0.01" min="0" x-model.number="it.precio_unit"
		                                                               class="w-full max-w-[7rem] text-right rounded-xl border-slate-200 bg-white px-2.5 py-1.5 text-sm focus:border-primary focus:ring-primary" />
		                                                    </td>
		                                                    <td class="px-3 py-2 text-right">
		                                                        <div class="inline-flex w-full max-w-[9rem] items-center justify-between rounded-xl border border-slate-200 bg-white">
		                                                            <button type="button" class="px-2 py-1.5 text-slate-600 hover:text-slate-900" @click="decQty(idx)">−</button>
		                                                            <input type="number" min="1" step="1" x-model.number="it.cantidad"
		                                                                   class="w-12 text-center border-0 focus:ring-0 text-sm" />
		                                                            <button type="button" class="px-2 py-1.5 text-slate-600 hover:text-slate-900" @click="incQty(idx)">+</button>
		                                                        </div>
		                                                    </td>
		                                                    <td class="px-3 py-2 text-right">
		                                                        <input type="number" step="0.01" min="0" x-model.number="it.descuento"
		                                                               class="w-full max-w-[6rem] text-right rounded-xl border-slate-200 bg-white px-2.5 py-1.5 text-sm focus:border-primary focus:ring-primary" />
		                                                    </td>
		                                                    <td class="px-3 py-2 text-right text-sm font-semibold text-slate-900 whitespace-nowrap" x-text="money(itemTotal(it), form.moneda)"></td>
		                                                    <td class="px-3 py-2 text-right">
		                                                        <button type="button" class="rounded-lg p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50"
		                                                                @click="removeItem(idx)" aria-label="Quitar">
		                                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
	                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
	                                                            </svg>
	                                                        </button>
	                                                    </td>
	                                                </tr>
	                                            </template>
		                                            <tr x-show="items.length === 0">
		                                                <td colspan="6" class="px-3 py-10 text-center text-sm text-slate-500">Agrega productos o servicios.</td>
		                                            </tr>
		                                                </tbody>
		                                            </table>
		                                        </div>
		                                    </div>
		                                </div>

	                                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs">
	                                    <div class="text-slate-500">
	                                        P.U. <span class="font-semibold text-slate-700">incluye IGV</span>.
	                                    </div>
	                                    <div class="text-slate-600">
	                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">Total</span>
	                                        <span class="ml-2 font-semibold text-slate-900" x-text="money(totalVenta, form.moneda)"></span>
	                                    </div>
	                                </div>
	                            </div>
	                        </div>

	                        <!-- Checkout / Resumen -->
	                        <div class="space-y-5 self-start lg:sticky lg:top-4">
	                            <div class="space-y-5">
	                            <div class="rounded-2xl border border-slate-200 bg-white p-5">
	                                <div class="flex items-center justify-between gap-3">
	                                    <div class="text-xs font-semibold text-slate-900">Cliente</div>
	                                    <button type="button" class="text-xs font-semibold text-slate-600 hover:text-slate-900" @click="clearCliente()" x-show="clienteSel">Quitar</button>
	                                </div>
	                                <div class="mt-4 relative">
	                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
	                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
	                                            <path fill-rule="evenodd" d="M9 3a6 6 0 104.472 10.03l2.249 2.249a1 1 0 001.414-1.414l-2.249-2.249A6 6 0 009 3zm-4 6a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
	                                        </svg>
	                                    </div>
	                                    <input x-model="cliQ" @input="queueClienteSearch($event.target.value)" @focus="cliOpen=true; queueClienteSearch(cliQ)" @keydown.escape="cliOpen=false"
	                                           placeholder="Buscar por teléfono, nombre, empresa, documento, email, dirección…"
	                                           class="w-full rounded-xl border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-primary focus:ring-primary" />

	                                    <div x-show="cliOpen && cliQ.trim().length >= 2" x-transition.opacity class="absolute z-20 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
	                                        <div class="max-h-64 overflow-auto">
	                                            <template x-for="c in cliResults" :key="c.id">
	                                                <button type="button" class="w-full text-left px-4 py-3 hover:bg-slate-50"
	                                                        @click="selectCliente(c); cliQ=''; cliOpen=false">
	                                                    <div class="flex items-start justify-between gap-3">
	                                                        <div class="min-w-0">
	                                                            <div class="text-sm font-medium text-slate-900 truncate" x-text="c.nombre || c.razon_social || c.telefono"></div>
	                                                            <div class="mt-0.5 text-xs text-slate-500 truncate" x-text="[c.razon_social, c.numero_documento, c.email, c.direccion].filter(Boolean).join(' · ') || 'Sin datos adicionales'"></div>
	                                                        </div>
	                                                        <div class="text-xs font-semibold text-slate-600 whitespace-nowrap" x-text="c.telefono"></div>
	                                                    </div>
	                                                </button>
	                                            </template>
	                                            <div class="px-4 py-6 text-sm text-slate-500" x-show="cliLoading">Buscando…</div>
	                                            <div class="px-4 py-6 text-sm text-rose-600" x-show="!cliLoading && cliError" x-text="cliError"></div>
	                                            <div class="px-4 py-6 text-sm text-slate-500" x-show="!cliLoading && !cliError && cliResults.length === 0">Sin resultados.</div>
	                                        </div>
	                                    </div>
	                                </div>

	                                <template x-if="clienteSel">
	                                    <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50/60 p-3">
	                                        <div class="text-sm font-semibold text-slate-900" x-text="clienteSel.nombre || 'Cliente'"></div>
	                                        <div class="mt-0.5 text-xs text-slate-600" x-text="clienteSel.telefono || ''"></div>
	                                        <template x-if="clienteSel.razon_social">
	                                            <div class="mt-2">
	                                                <span class="inline-flex items-center rounded-full bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200"
	                                                      x-text="clienteSel.razon_social"></span>
	                                            </div>
	                                        </template>
	                                    </div>
	                                </template>
	                            </div>

	                            <div class="rounded-2xl border border-slate-200 bg-white p-5 space-y-3">
	                                <div class="flex items-center justify-between">
	                                    <div class="text-xs font-semibold text-slate-900">Comprobante</div>
	                                    <div class="flex items-center gap-2">
	                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200" x-text="form.tipo_documento"></span>
	                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200" x-text="form.moneda"></span>
	                                    </div>
	                                </div>
	                                <div class="grid grid-cols-2 gap-3">
	                                    <div>
	                                        <label class="text-xs font-medium text-slate-700">Tipo</label>
	                                        <select x-model="form.tipo_documento" @change="refreshCorrelativo()"
	                                                class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
	                                            <option value="NOTA_VENTA">NOTA_VENTA</option>
	                                            <option value="FACTURA">FACTURA</option>
	                                            <option value="BOLETA">BOLETA</option>
	                                        </select>
	                                    </div>
	                                    <div>
	                                        <label class="text-xs font-medium text-slate-700">Serie</label>
	                                        <input x-model="form.serie_documento" @change="refreshCorrelativo()"
	                                               class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
	                                    </div>
	                                </div>
	                                <div class="grid grid-cols-2 gap-3">
	                                    <div>
	                                        <label class="text-xs font-medium text-slate-700">Número</label>
	                                        <input x-model="form.numero_documento"
	                                               :disabled="String(form.tipo_documento).toUpperCase() === 'NOTA_VENTA'"
	                                               :class="String(form.tipo_documento).toUpperCase() === 'NOTA_VENTA' ? 'bg-slate-50 text-slate-700 cursor-not-allowed' : ''"
	                                               class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
	                                        <div class="mt-1 text-[11px] text-slate-500">
	                                            Consecutivo:
	                                            <span class="font-semibold text-slate-900" x-text="correlativoNext || '—'"></span>
	                                            <template x-if="String(form.tipo_documento).toUpperCase() !== 'NOTA_VENTA'">
	                                                <span class="text-slate-400"> (puedes editar)</span>
	                                            </template>
	                                        </div>
	                                        <template x-if="String(form.tipo_documento).toUpperCase() === 'NOTA_VENTA' && form.serie_documento && form.numero_documento">
	                                            <div class="mt-2 rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2 text-xs text-slate-700">
	                                                Nota de venta:
	                                                <span class="font-semibold text-slate-900" x-text="String(form.serie_documento) + '-' + String(form.numero_documento)"></span>
	                                            </div>
	                                        </template>
	                                    </div>
	                                    <div>
	                                        <label class="text-xs font-medium text-slate-700">Sucursal</label>
	                                        <select x-model="form.sucursal_id" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
	                                            <option value="">—</option>
	                                            <template x-for="s in sucursales" :key="s.id">
	                                                <option :value="String(s.id)" x-text="(s.codigo ? (s.codigo+' - ') : '') + (s.nombre || ('Sucursal '+s.id))"></option>
	                                            </template>
	                                        </select>
	                                    </div>
	                                </div>

	                                <div class="grid grid-cols-2 gap-3">
	                                    <div class="col-span-2">
	                                        <label class="text-xs font-medium text-slate-700">Vendedor</label>
	                                        <select x-model="form.vendedor_id" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
	                                            <option value="">— (actual)</option>
	                                            <template x-for="u in usuarios" :key="u.id">
	                                                <option :value="String(u.id)" x-text="u.nombre || ('Usuario '+u.id)"></option>
	                                            </template>
	                                        </select>
	                                    </div>
	                                </div>

	                                <div class="grid grid-cols-2 gap-3">
	                                    <div>
	                                        <label class="text-xs font-medium text-slate-700">Moneda</label>
	                                        <select x-model="form.moneda" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
	                                            <option value="PEN">PEN</option>
	                                            <option value="USD">USD</option>
	                                        </select>
	                                    </div>
	                                    <div>
	                                        <label class="text-xs font-medium text-slate-700">Tipo cambio</label>
	                                        <input x-model="form.tipo_cambio" type="number" step="0.0001"
	                                               class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
	                                    </div>
	                                </div>

	                                <div class="grid grid-cols-2 gap-3">
	                                    <div>
	                                        <label class="text-xs font-medium text-slate-700">Estado inicial</label>
	                                        <select x-model="form.estado" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
	                                            <option value="PENDIENTE">PENDIENTE</option>
	                                            <option value="PAGADA">PAGADA</option>
	                                        </select>
	                                    </div>
	                                    <div x-show="form.estado === 'PAGADA'">
	                                        <label class="text-xs font-medium text-slate-700">Método pago</label>
	                                        <select x-model="form.metodo_pago" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
	                                            <option value="">—</option>
	                                            <option value="EFECTIVO">Efectivo</option>
	                                            <option value="YAPE">Yape</option>
	                                            <option value="PLIN">Plin</option>
	                                            <option value="TRANSFERENCIA">Transferencia / Depósito</option>
	                                            <option value="TARJETA">Tarjeta</option>
	                                            <option value="POS">POS</option>
	                                            <option value="CUOTAS_SIN_INTERES">Cuotas sin interés</option>
	                                        </select>
	                                        <div class="mt-1 text-[11px] text-slate-500">Se registra el pago por el total.</div>
	                                    </div>
	                                </div>

	                                <div class="grid grid-cols-2 gap-3" x-show="form.estado === 'PAGADA' && needsOperacion">
	                                    <div class="col-span-2">
	                                        <label class="text-xs font-medium text-slate-700">N° operación / referencia</label>
	                                        <input x-model="form.pago_referencia" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" placeholder="Ej: 123456789" />
	                                    </div>
	                                </div>

	                                <div class="grid grid-cols-2 gap-3" x-show="form.estado === 'PAGADA' && String(form.metodo_pago).toUpperCase() === 'CUOTAS_SIN_INTERES'">
	                                    <div class="col-span-2">
	                                        <label class="text-xs font-medium text-slate-700">Banco</label>
	                                        <select x-model="form.banco" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
	                                            <option value="">—</option>
	                                            <option value="BCP">BCP</option>
	                                            <option value="INTERBANK">Interbank</option>
	                                            <option value="BBVA">BBVA</option>
	                                            <option value="SCOTIABANK">Scotiabank</option>
	                                            <option value="BANBIF">BanBif</option>
	                                            <option value="PICHINCHA">Pichincha</option>
	                                            <option value="CAJA">Caja</option>
	                                            <option value="OTRO">Otro</option>
	                                        </select>
	                                    </div>
	                                    <div class="col-span-2">
	                                        <div class="flex items-center justify-between">
	                                            <label class="text-xs font-medium text-slate-700">N° de cuotas</label>
	                                            <span class="text-[11px] text-slate-500" title="Solo informativo para la venta; no afecta el cálculo del total.">?</span>
	                                        </div>
	                                        <input x-model="form.cuotas" type="number" min="2" step="1" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" placeholder="Ej: 3" />
	                                    </div>
	                                </div>
	                            </div>

	                            <div class="rounded-2xl border border-slate-200 bg-white p-5">
	                                <div class="text-sm font-semibold text-slate-900">Resumen</div>
	                                <dl class="mt-3 space-y-2 text-sm">
	                                    <div class="flex items-center justify-between gap-3">
	                                        <dt class="text-slate-500 font-medium">Valor venta</dt>
	                                        <dd class="text-slate-900 font-semibold" x-text="money(needsIgvBreakdown ? baseImponible : totalVenta, form.moneda)"></dd>
	                                    </div>
	                                    <div class="flex items-center justify-between gap-3">
	                                        <dt class="text-slate-500 font-medium">IGV (18%)</dt>
	                                        <dd class="text-slate-900 font-semibold" x-text="money(needsIgvBreakdown ? igvMonto : 0, form.moneda)"></dd>
	                                    </div>
	                                    <div class="flex items-center justify-between gap-3 border-t border-slate-200 pt-2">
	                                        <dt class="text-slate-500 font-medium">Total (precio final)</dt>
	                                        <dd class="text-slate-900 font-semibold" x-text="money(totalVenta, form.moneda)"></dd>
	                                    </div>
	                                </dl>
	                                <div class="mt-3 text-xs text-slate-500">
	                                    * El precio unitario es el precio final (incluye IGV). Para Factura/Boleta, el sistema desagrega Valor venta e IGV desde el total.
	                                </div>
	                            </div>
	                            </div>
	                        </div>
	                    </div>
	                </div>
                <div class="px-6 py-4 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50" @click="$dispatch('close-modal','venta-form')">Cancelar</button>
                    <button class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90 disabled:opacity-60" :disabled="saving">
                        <svg x-show="saving" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span x-text="saving ? 'Registrando…' : 'Crear venta'"></span>
                    </button>
                </div>
            </form>
        </x-modal>

        <!-- Pagar -->
        <x-modal name="venta-pagar" maxWidth="2xl" focusable>
            <form class="divide-y divide-slate-200" @submit.prevent="submitPagar()">
                <div class="px-6 py-4 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Pagar venta</div>
                        <div class="mt-0.5 text-xs text-slate-500">Marca como PAGADA y aplica stock/comisión</div>
                    </div>
                    <x-icon-button @click="$dispatch('close-modal','venta-pagar')" aria-label="Cerrar">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </x-icon-button>
                </div>
                <div class="px-6 py-5 space-y-3">
                    <template x-if="actionError">
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="actionError"></div>
                    </template>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-medium text-slate-700">Método</label>
                            <input x-model="pay.metodo" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Referencia</label>
                            <input x-model="pay.referencia" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50" @click="$dispatch('close-modal','venta-pagar')">Cancelar</button>
                    <button class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90" :disabled="savingAction" x-text="savingAction ? 'Procesando…' : 'Confirmar pago'"></button>
                </div>
            </form>
        </x-modal>

        <!-- Anular -->
        <x-modal name="venta-anular" maxWidth="2xl" focusable>
            <form class="divide-y divide-slate-200" @submit.prevent="submitAnular()">
                <div class="px-6 py-4 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Anular venta</div>
                        <div class="mt-0.5 text-xs text-slate-500">Si estaba pagada, hace devolución a stock</div>
                    </div>
                    <x-icon-button @click="$dispatch('close-modal','venta-anular')" aria-label="Cerrar">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </x-icon-button>
                </div>
                <div class="px-6 py-5 space-y-3">
                    <template x-if="actionError">
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="actionError"></div>
                    </template>
                    <div>
                        <label class="text-xs font-medium text-slate-700">Motivo (opcional)</label>
                        <input x-model="cancel.motivo" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                    </div>
                </div>
                <div class="px-6 py-4 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50" @click="$dispatch('close-modal','venta-anular')">Cancelar</button>
                    <button class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700" :disabled="savingAction" x-text="savingAction ? 'Anulando…' : 'Confirmar anulación'"></button>
                </div>
            </form>
        </x-modal>

        <script>
            function ventasPage({ urls }) {
                return {
                    urls,
                    q: '',
                    rows: [],
                    loading: false,
                    page: 0,
	                    perPage: 25,
	                    detail: null,
	                    detailCliente: null,
	                    detailItems: [],
	                    detailPagos: [],
	                    detailError: '',
	                    kpis: { pagadasMes: '—', pendientes: '—', totalMes: '—', moneda: 'PEN' },
		                    usuarios: [],
		                    sucursales: [],
	                    prodResults: [],
	                    prodLoading: false,
	                    prodQ: '',
	                    prodOpen: false,
	                    cliResults: [],
	                    cliLoading: false,
	                    cliError: '',
	                    cliAbort: null,
	                    cliTimer: null,
	                    cliQ: '',
	                    cliOpen: false,
	                    clienteSel: null,
	                    correlativoNext: null,
	                    items: [],
		                    form: {
		                        tipo_documento: 'NOTA_VENTA',
		                        serie_documento: 'NV01',
		                        numero_documento: '',
		                        moneda: 'PEN',
		                        tipo_cambio: '',
		                        cliente_id: '',
		                        sucursal_id: '',
		                        vendedor_id: '',
		                        estado: 'PENDIENTE',
		                        metodo_pago: '',
		                        pago_referencia: '',
		                        banco: '',
		                        cuotas: '',
		                        notas: '',
		                        items_json: '[]',
		                    },
	                    formError: '',
	                    saving: false,
                    pay: { metodo: '', referencia: '' },
                    cancel: { motivo: '' },
                    actionError: '',
                    savingAction: false,

	                    async init() {
	                        window.addEventListener('venta-new', () => this.resetVentaForm());

	                        await Promise.all([
	                            this.loadUsuarios(),
	                            this.loadSucursales(),
	                        ]);
	                        await this.reload();
	                        await this.refreshKpis();
		                    },

		                    resetVentaForm() {
	                        this.formError = '';
	                        this.prodQ = '';
	                        this.cliQ = '';
	                        this.cliResults = [];
	                        this.cliError = '';
	                        clearTimeout(this.cliTimer);
	                        this.cliTimer = null;
	                        this.cliAbort?.abort();
	                        this.cliAbort = null;
	                        this.prodOpen = false;
	                        this.cliOpen = false;
	                        this.clienteSel = null;
	                        this.correlativoNext = null;
	                        this.items = [];
		                        this.form = {
		                            tipo_documento: 'NOTA_VENTA',
		                            serie_documento: 'NV01',
		                            numero_documento: '',
		                            moneda: 'PEN',
		                            tipo_cambio: '',
		                            cliente_id: '',
		                            sucursal_id: '',
		                            vendedor_id: '',
		                            estado: 'PENDIENTE',
		                            metodo_pago: '',
		                            pago_referencia: '',
		                            banco: '',
		                            cuotas: '',
		                            notas: '',
		                            items_json: '[]',
		                        };
		                        this.refreshCorrelativo();
		                    },

	                    async loadProductos() {
	                        // Deprecated: se reemplazó por búsqueda remota (typeahead) con q/limit.
	                    },

		                    async loadClientes() {
		                        // Deprecated: se reemplazó por búsqueda remota (typeahead) con q/limit.
		                    },

	                    async searchProductos() {
	                        const q = String(this.prodQ || '').trim();
	                        if (q.length < 2) {
	                            this.prodResults = [];
	                            this.prodLoading = false;
	                            return;
	                        }
	                        this.prodLoading = true;
	                        try {
	                            const res = await window.axios.get(this.urls.productos, {
	                                headers: { 'Accept': 'application/json' },
	                                params: { q, limit: 20 },
	                            });
	                            const rows = res.data?.data || [];
	                            this.prodResults = (Array.isArray(rows) ? rows : []).slice(0, 20);
	                        } catch {
	                            this.prodResults = [];
	                        } finally {
	                            this.prodLoading = false;
	                        }
	                    },

	                    queueClienteSearch(rawQuery = this.cliQ) {
	                        this.cliQ = String(rawQuery || '');
	                        clearTimeout(this.cliTimer);
	                        this.cliTimer = null;
	                        this.cliAbort?.abort();
	                        this.cliAbort = null;
	                        this.cliResults = [];
	                        this.cliError = '';
	                        if (this.cliQ.trim().length < 2) {
	                            this.cliLoading = false;
	                            return;
	                        }
	                        this.cliLoading = true;
	                        this.cliTimer = setTimeout(() => {
	                            this.cliTimer = null;
	                            this.searchClientes(this.cliQ);
	                        }, 220);
	                    },

	                    async searchClientes(rawQuery = this.cliQ) {
	                        const q = String(rawQuery || '').trim();
	                        this.cliAbort?.abort();
	                        if (q.length < 2) {
	                            this.cliResults = [];
	                            this.cliLoading = false;
	                            this.cliError = '';
	                            this.cliAbort = null;
	                            return;
	                        }
	                        const controller = new AbortController();
	                        this.cliAbort = controller;
	                        this.cliLoading = true;
	                        this.cliError = '';
	                        try {
	                            const res = await window.axios.get(this.urls.clientes, {
	                                headers: { 'Accept': 'application/json' },
	                                params: { q, limit: 15, typeahead: 1 },
	                                signal: controller.signal,
	                            });
	                            if (String(this.cliQ || '').trim() !== q) return;
	                            const rows = res.data?.data || [];
	                            this.cliResults = (Array.isArray(rows) ? rows : []).slice(0, 15);
	                        } catch (error) {
	                            if (error?.code === 'ERR_CANCELED' || error?.name === 'CanceledError') return;
	                            this.cliResults = [];
	                            this.cliError = 'No se pudo completar la búsqueda.';
	                        } finally {
	                            if (this.cliAbort === controller) {
	                                this.cliLoading = false;
	                                this.cliAbort = null;
	                            }
	                        }
	                    },

		                    async loadUsuarios() {
		                        try {
		                            const res = await window.axios.get(this.urls.usuarios, { headers: { 'Accept': 'application/json' } });
		                            this.usuarios = res.data?.data || [];
		                        } catch {
		                            this.usuarios = [];
		                        }
		                    },

	                    async loadSucursales() {
	                        try {
	                            const res = await window.axios.get(this.urls.sucursales, { headers: { 'Accept': 'application/json' } });
	                            this.sucursales = res.data?.data || [];
	                        } catch {
	                            this.sucursales = [];
	                        }
	                    },

                    async reload() {
                        this.loading = true;
                        try {
                            const res = await window.axios.get(this.urls.data, { headers: { 'Accept': 'application/json' } });
                            this.rows = res.data?.data || [];
                            this.page = 0;
                        } finally {
                            this.loading = false;
                        }
                    },

                    get filteredRows() {
                        const q = (this.q || '').trim().toLowerCase();
                        if (!q) return this.rows;
                        return this.rows.filter((r) => this.rowSearchText(r).includes(q));
                    },

                    get pages() {
                        return Math.max(1, Math.ceil(this.filteredRows.length / this.perPage));
                    },

                    get pagedRows() {
                        const start = this.page * this.perPage;
                        return this.filteredRows.slice(start, start + this.perPage);
                    },

                    applySearch() {
                        this.page = 0;
                    },

                    async refreshKpis() {
                        const now = new Date();
                        const first = new Date(now.getFullYear(), now.getMonth(), 1);
                        const last = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59);
                        const toSql = (d) => {
                            const pad = (v) => String(v).padStart(2, '0');
                            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
                        };
                        const fmt = (n) => Number(n || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                        try {
                            const res = await window.axios.get(this.urls.stats, {
                                headers: { 'Accept': 'application/json' },
                                params: { from: toSql(first), to: toSql(last) },
                            });
                            const data = res.data?.data || {};
                            if (res.data?.ok === true && !Array.isArray(data)) {
                                this.kpis.pendientes = Number(data.pendientes_mes || 0);
                                this.kpis.pagadasMes = Number(data.pagadas_mes || 0);
                                this.kpis.totalMes = fmt(data.total_mes || 0);
                                this.kpis.moneda = data.moneda || 'PEN';
                                return;
                            }
                        } catch {
                            // fallback local
                        }

                        const inMonth = (v) => {
                            if (!v) return false;
                            const d = new Date(String(v).replace(' ', 'T'));
                            return !Number.isNaN(d.getTime()) && d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth();
                        };
                        const monthRows = (this.rows || []).filter(r => inMonth(r.fecha_venta));
                        this.kpis.pendientes = monthRows.filter(r => String(r.estado || '').toUpperCase() === 'PENDIENTE').length;
                        this.kpis.pagadasMes = monthRows.filter(r => String(r.estado || '').toUpperCase() === 'PAGADA').length;
                        this.kpis.totalMes = fmt(monthRows.filter(r => String(r.estado || '').toUpperCase() !== 'ANULADA').reduce((acc, r) => acc + Number(r.total || 0), 0));
                        this.kpis.moneda = (monthRows[0]?.moneda) || (this.rows?.[0]?.moneda) || 'PEN';
                    },

                    async openDetail(id) {
                        this.detailError = '';
                        this.detail = null;
                        this.detailCliente = null;
                        this.detailItems = [];
                        this.detailPagos = [];
                        this.actionError = '';
                        this.pay = { metodo: '', referencia: '' };
                        this.cancel = { motivo: '' };
                        this.$dispatch('open-modal', 'venta-detalle');
                        try {
                            const res = await window.axios.get(this.urls.show(id), { headers: { 'Accept': 'application/json' } });
                            const data = res.data?.data || null;
                            this.detail = data?.venta ? (data.venta || null) : data;
                            this.detailCliente = data?.cliente || null;
                            this.detailItems = data?.items || [];
                            this.detailPagos = data?.pagos || [];
                            if (res.data?.ok !== true) {
                                this.detailError = res.data?.error || 'No se pudo cargar.';
                            }
                        } catch (e) {
                            this.detailError = e?.response?.data?.error || e?.message || 'Error';
                        }
                    },

	                    async submitForm() {
	                        this.formError = '';
	                        this.saving = true;
	                        try {
	                            if (this.items.length === 0) {
	                                this.formError = 'Agrega al menos 1 item.';
	                                return;
	                            }

	                            const tipo = String(this.form.tipo_documento || 'NOTA_VENTA').toUpperCase();
	                            if ((tipo === 'FACTURA' || tipo === 'BOLETA') && (!this.form.serie_documento || !this.form.numero_documento)) {
	                                this.formError = 'Factura/Boleta requiere serie y número.';
	                                return;
	                            }

	                            const itemsPayload = this.items.map((it) => ({
	                                producto_id: it.producto_id ? Number(it.producto_id) : null,
	                                descripcion: String(it.descripcion || '').slice(0, 255),
	                                cantidad: Math.max(1, parseInt(String(it.cantidad ?? 1), 10) || 1),
	                                precio_unit: Math.max(0, Number(it.precio_unit ?? 0) || 0),
	                                descuento: Math.max(0, Number(it.descuento ?? 0) || 0),
	                            }));
	                            this.form.items_json = JSON.stringify(itemsPayload);

	                            const payload = {
	                                cliente_id: this.form.cliente_id ? Number(this.form.cliente_id) : null,
	                                sucursal_id: this.form.sucursal_id ? Number(this.form.sucursal_id) : null,
	                                vendedor_id: this.form.vendedor_id ? Number(this.form.vendedor_id) : null,
	                                tipo_documento: this.form.tipo_documento,
	                                serie_documento: this.form.serie_documento || null,
	                                // Para NOTA_VENTA el correlativo se autogenera en backend (se muestra como sugerido en UI)
	                                numero_documento: tipo === 'NOTA_VENTA' ? null : (this.form.numero_documento || null),
	                                moneda: this.form.moneda,
	                                tipo_cambio: this.form.tipo_cambio ? Number(this.form.tipo_cambio) : null,
	                                metodo_pago: this.form.estado === 'PAGADA' ? (this.form.metodo_pago || null) : null,
	                                pago_referencia: this.form.estado === 'PAGADA' ? (this.pagoReferenciaComputed() || null) : null,
	                                notas: (this.form.estado === 'PAGADA' && String(this.form.metodo_pago || '').toUpperCase() === 'CUOTAS_SIN_INTERES') ? (this.notasComputed() || null) : null,
	                                estado: this.form.estado || null,
	                                items_json: this.form.items_json,
	                            };
	                            const res = await window.axios.post(this.urls.create, payload, { headers: { 'Accept': 'application/json' } });
	                            if (res.data?.ok !== true) {
	                                const msg = res.data?.error || 'No se pudo crear la venta.';
	                                this.formError = msg;
	                                window.GCToast?.error('No se pudo crear la venta', msg);
	                                return;
	                            }
	                            const warnings = res.data?.data?.warnings || res.data?.data?.venta?.warnings || [];
	                            this.$dispatch('close-modal', 'venta-form');
	                            await this.reload();
	                            await this.refreshKpis();
	                            if (Array.isArray(warnings) && warnings.length > 0) {
	                                const lines = warnings.map((w) => {
	                                    const desc = w.descripcion || `Producto #${w.producto_id || '—'}`;
	                                    const need = w.cantidad_requerida != null ? ` (req: ${w.cantidad_requerida})` : '';
	                                    const other = (w.sucursales_usadas || w.sucursales_con_stock || []).map((s) => {
	                                        const n = s.sucursal_nombre || s.sucursal_codigo || `Sede #${s.sucursal_id || '—'}`;
	                                        const st = s.stock != null ? ` (${s.stock})` : (s.cantidad != null ? ` (${s.cantidad})` : '');
	                                        return `${n}${st}`;
	                                    }).join(', ');
	                                    return `• ${desc}${need}: stock disponible en: ${other}`;
	                                }).join('\n');
	                                window.GCToast?.info('Stock en otra sede', lines, { timeoutMs: 10000 });
	                            }
	                            window.GCToast?.success('Venta creada', 'La venta se registró correctamente.');
	                        } catch (e) {
	                            const msg = e?.response?.data?.error || e?.response?.data?.message || e?.message || 'Error';
	                            this.formError = msg;
	                            window.GCToast?.error('No se pudo crear la venta', msg);
	                        } finally {
                            this.saving = false;
                        }
                    },

                    async submitPagar() {
                        this.actionError = '';
                        this.savingAction = true;
                        try {
                            const res = await window.axios.post(this.urls.pagar(this.detail?.id), this.pay, { headers: { 'Accept': 'application/json' } });
                            if (res.data?.ok !== true) {
                                this.actionError = res.data?.error || 'No se pudo pagar.';
                                return;
                            }
                            const warnings = res.data?.data?.warnings || [];
                            this.$dispatch('close-modal', 'venta-pagar');
                            await this.reload();
                            await this.refreshKpis();
                            if (Array.isArray(warnings) && warnings.length > 0) {
                                const lines = warnings.map((w) => {
                                    const desc = w.descripcion || `Producto #${w.producto_id || '—'}`;
                                    const other = (w.sucursales_usadas || w.sucursales_con_stock || []).map((s) => {
                                        const n = s.sucursal_nombre || s.sucursal_codigo || `Sede #${s.sucursal_id || '—'}`;
                                        const st = s.stock != null ? ` (${s.stock})` : (s.cantidad != null ? ` (${s.cantidad})` : '');
                                        return `${n}${st}`;
                                    }).join(', ');
                                    return `• ${desc}: stock descontado desde otra(s) sede(s): ${other}`;
                                }).join('\n');
                                window.GCToast?.warning('Pago registrado (stock desde otra sede)', lines, { timeoutMs: 9000 });
                            } else {
                                window.GCToast?.success('Pago registrado', 'La venta fue marcada como PAGADA.');
                            }
                        } catch (e) {
                            this.actionError = e?.response?.data?.error || e?.message || 'Error';
                        } finally {
                            this.savingAction = false;
                        }
                    },

	                    async submitAnular() {
	                        this.actionError = '';
	                        this.savingAction = true;
	                        try {
                            const res = await window.axios.post(this.urls.anular(this.detail?.id), this.cancel, { headers: { 'Accept': 'application/json' } });
                            if (res.data?.ok !== true) {
                                this.actionError = res.data?.error || 'No se pudo anular.';
                                return;
                            }
                            this.$dispatch('close-modal', 'venta-anular');
                            await this.reload();
                            await this.refreshKpis();
                            window.GCToast?.success('Venta anulada', 'La venta fue anulada.');
                        } catch (e) {
                            this.actionError = e?.response?.data?.error || e?.message || 'Error';
                        } finally {
                            this.savingAction = false;
                        }
                    },

                    clienteVenta(row) {
                        if (!row) return 'Cliente no registrado';
                        return row.cliente_nombre || row.cliente_razon_social || row.cliente_razon || (row.cliente_id ? `Cliente #${row.cliente_id}` : 'Cliente no registrado');
                    },

                    clienteVentaTelefono(row) {
                        return row?.cliente_telefono || '';
                    },

                    clienteVentaDocumento(row) {
                        const tipo = row?.cliente_tipo_documento || row?.cliente_doc_tipo || '';
                        const num = row?.cliente_numero_documento || row?.cliente_doc_num || '';
                        return tipo && num ? `${tipo} ${num}` : (num || '');
                    },

                    detailClienteNombre() {
                        return this.detailCliente?.nombre || this.detailCliente?.razon_social || this.detail?.cliente_razon || this.clienteVenta(this.detail);
                    },

                    detailClienteTelefono() {
                        return this.detailCliente?.telefono || this.detail?.cliente_telefono || '';
                    },

                    detailClienteDocumento() {
                        const tipo = this.detailCliente?.tipo_documento || this.detail?.cliente_doc_tipo || '';
                        const num = this.detailCliente?.numero_documento || this.detail?.cliente_doc_num || '';
                        return tipo && num ? `${tipo} ${num}` : (num || '');
                    },

                    detailClienteDireccion() {
                        return this.detailCliente?.direccion || this.detail?.cliente_direccion || this.detail?.cliente_direccion_ref || '';
                    },

                    detailClienteEmail() {
                        return this.detailCliente?.email || this.detail?.cliente_email || '';
                    },

                    detailClienteBadge() {
                        return this.detailCliente?.tipo_documento || this.detail?.cliente_doc_tipo || 'Cliente';
                    },

	                    get filteredProductos() {
	                        const q = (this.prodQ || '').trim().toLowerCase();
	                        if (!q) return [];
	                        return (this.productos || []).filter((p) => {
	                            const s = `${p.sku || ''} ${p.nombre || ''} ${p.modelo || ''}`.toLowerCase();
	                            return s.includes(q);
	                        });
	                    },

	                    get filteredClientes() {
	                        const q = (this.cliQ || '').trim().toLowerCase();
	                        if (!q) return [];
	                        return (this.clientes || []).filter((c) => this.rowSearchText(c).includes(q));
	                    },

	                    rowSearchText(r) {
	                        try {
	                            return Object.values(r || {})
	                                .map((v) => {
	                                    if (v === null || v === undefined) return '';
	                                    if (typeof v === 'string') return v;
	                                    if (typeof v === 'number' || typeof v === 'boolean') return String(v);
	                                    return '';
	                                })
	                                .join(' ')
	                                .toLowerCase();
	                        } catch {
	                            return '';
	                        }
	                    },

	                    addProducto(p) {
	                        if (!p) return;
	                        const pid = Number(p.id || 0) || null;
	                        const existingIdx = this.items.findIndex((it) => (it.producto_id || null) === pid && pid !== null);
	                        if (existingIdx >= 0) {
	                            this.items[existingIdx].cantidad = Math.max(1, Number(this.items[existingIdx].cantidad || 1) + 1);
	                            return;
	                        }
	                        this.items.push({
	                            _k: `${Date.now()}_${Math.random().toString(16).slice(2)}`,
	                            producto_id: pid,
	                            sku: p.sku || '',
	                            descripcion: p.nombre || 'Producto',
	                            cantidad: 1,
	                            precio_unit: Number(p.precio || 0) || 0,
	                            descuento: 0,
	                        });
	                    },

	                    addServicio() {
	                        this.items.push({
	                            _k: `${Date.now()}_${Math.random().toString(16).slice(2)}`,
	                            producto_id: null,
	                            sku: '',
	                            descripcion: 'Servicio',
	                            cantidad: 1,
	                            precio_unit: 0,
	                            descuento: 0,
	                        });
	                    },

	                    removeItem(idx) {
	                        this.items.splice(idx, 1);
	                    },

	                    incQty(idx) {
	                        const it = this.items[idx];
	                        if (!it) return;
	                        it.cantidad = Math.max(1, Number(it.cantidad || 1) + 1);
	                    },

	                    decQty(idx) {
	                        const it = this.items[idx];
	                        if (!it) return;
	                        it.cantidad = Math.max(1, Number(it.cantidad || 1) - 1);
	                    },

	                    itemTotal(it) {
	                        const qty = Math.max(1, parseInt(String(it?.cantidad ?? 1), 10) || 1);
	                        const pu = Math.max(0, Number(it?.precio_unit ?? 0) || 0);
	                        const desc = Math.max(0, Number(it?.descuento ?? 0) || 0);
	                        const line = (qty * pu) - desc;
	                        return Math.max(0, Number(line.toFixed(2)));
	                    },

	                    get totalVenta() {
	                        return Number((this.items || []).reduce((acc, it) => acc + this.itemTotal(it), 0).toFixed(2));
	                    },

	                    get needsIgvBreakdown() {
	                        const t = String(this.form.tipo_documento || '').toUpperCase();
	                        return t === 'FACTURA' || t === 'BOLETA';
	                    },

	                    get baseImponible() {
	                        if (!this.needsIgvBreakdown) return 0;
	                        const total = this.totalVenta;
	                        const factor = 1 + (18 / 100);
	                        return Number((total / factor).toFixed(2));
	                    },

	                    get igvMonto() {
	                        if (!this.needsIgvBreakdown) return 0;
	                        return Number((this.totalVenta - this.baseImponible).toFixed(2));
	                    },

	                    selectCliente(c) {
	                        this.cliAbort?.abort();
	                        this.cliAbort = null;
	                        clearTimeout(this.cliTimer);
	                        this.cliTimer = null;
	                        this.cliLoading = false;
	                        this.cliError = '';
	                        this.cliResults = [];
	                        this.clienteSel = c || null;
	                        this.form.cliente_id = c?.id ? String(c.id) : '';
	                    },

	                    clearCliente() {
	                        this.cliAbort?.abort();
	                        this.cliAbort = null;
	                        clearTimeout(this.cliTimer);
	                        this.cliTimer = null;
	                        this.cliLoading = false;
	                        this.cliError = '';
	                        this.cliResults = [];
	                        this.cliQ = '';
	                        this.clienteSel = null;
	                        this.form.cliente_id = '';
	                    },

	                    async refreshCorrelativo() {
	                        const tipo = String(this.form.tipo_documento || 'NOTA_VENTA').toUpperCase();
	                        if (!this.form.serie_documento && tipo === 'NOTA_VENTA') {
	                            this.form.serie_documento = 'NV01';
	                        }
	                        try {
	                            const res = await window.axios.get(this.urls.next, {
	                                headers: { 'Accept': 'application/json' },
	                                params: { tipo_documento: tipo, serie: this.form.serie_documento || undefined },
	                            });
	                            const next = res.data?.data?.next ?? null;
	                            this.correlativoNext = next ? String(next) : null;
	                            if (tipo === 'NOTA_VENTA') {
	                                this.form.numero_documento = this.correlativoNext || '';
	                            } else if (!this.form.numero_documento && this.correlativoNext) {
	                                this.form.numero_documento = this.correlativoNext;
	                            }
	                        } catch {
	                            this.correlativoNext = null;
	                        }
	                    },

	                    get needsOperacion() {
	                        const m = String(this.form.metodo_pago || '').toUpperCase();
	                        return ['YAPE', 'PLIN', 'TRANSFERENCIA', 'TARJETA', 'POS'].includes(m);
	                    },

	                    pagoReferenciaComputed() {
	                        const ref = String(this.form.pago_referencia || '').trim();
	                        return ref || null;
	                    },

	                    notasComputed() {
	                        const m = String(this.form.metodo_pago || '').toUpperCase();
	                        if (m !== 'CUOTAS_SIN_INTERES') return null;
	                        const banco = String(this.form.banco || '').trim();
	                        const cuotas = String(this.form.cuotas || '').trim();
	                        const parts = ['CSI'];
	                        if (banco) parts.push(banco);
	                        if (cuotas) parts.push(cuotas);
	                        return parts.join(' ') || null;
	                    },

	                    money(v, mon) {
	                        const n = (v === null || v === undefined) ? null : Number(v);
	                        if (n === null || Number.isNaN(n)) return '—';
                        const m = mon || '';
                        return n.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + (m ? ` ${m}` : '');
                    },
                json(v) {
                    try { return JSON.stringify(v, null, 2); } catch { return String(v); }
                },

                shortDateTime(v) {
                    if (!v) return '—';
                    const s = String(v);
                    const iso = s.replace(' ', 'T');
                    const d = new Date(iso);
                    if (Number.isNaN(d.getTime())) return s.slice(0, 16);
                    const dd = String(d.getDate()).padStart(2, '0');
                    const mm = String(d.getMonth() + 1).padStart(2, '0');
                    const yyyy = d.getFullYear();
                    const hh = String(d.getHours()).padStart(2, '0');
                    const mi = String(d.getMinutes()).padStart(2, '0');
                    return `${dd}/${mm}/${yyyy} ${hh}:${mi}`;
                },

                sucursalLabel(id) {
                    const n = Number(id || 0);
                    if (!n) return '—';
                    const s = (this.sucursales || []).find(x => Number(x.id) === n);
                    if (!s) return `Sucursal ${n}`;
                    return (s.codigo ? `${s.codigo} - ` : '') + (s.nombre || `Sucursal ${n}`);
                },

                usuarioLabel(id) {
                    const n = Number(id || 0);
                    if (!n) return '—';
                    const u = (this.usuarios || []).find(x => Number(x.id) === n);
                    return u?.nombre || `Usuario ${n}`;
                },

                    docLabel(r) {
                        const doc = [r.serie_documento, r.numero_documento].filter(Boolean).join('-');
                        return doc || '—';
                    },

                    estadoClass(v) {
                        const s = String(v || '').toUpperCase();
                        if (s === 'PAGADA') return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
                        if (s === 'PENDIENTE') return 'bg-amber-50 text-amber-800 ring-amber-200';
                        if (s === 'ANULADA') return 'bg-rose-50 text-rose-700 ring-rose-200';
                        return 'bg-slate-100 text-slate-700 ring-slate-200';
                    },
                }
            }
        </script>
    </div>
</x-app-layout>
