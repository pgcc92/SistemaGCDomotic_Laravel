<x-app-layout>
    <x-slot name="header">
        <div class="text-lg font-semibold text-slate-900">Productos / Stock</div>
    </x-slot>

    <div
        x-data="productosPage({
            urls: {
                data: '{{ route('productos.data') }}',
                show: (id) => `/productos/${id}`,
                create: '{{ route('productos.store') }}',
                update: (id) => `/productos/${id}/editar`,
                destroy: (id) => `/productos/${id}/eliminar`,
                movimiento: '{{ route('productos.movimiento') }}',
                import: '{{ route('productos.import') }}',
                sucursales: '{{ route('sucursales.data') }}',
            }
        })"
        x-init="init()"
        class="space-y-6"
    >
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3a6 6 0 104.472 10.03l2.249 2.249a1 1 0 001.414-1.414l-2.249-2.249A6 6 0 009 3zm-4 6a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input x-model.debounce.200ms="q" @input="page=0"
                       placeholder="Buscar por SKU o nombre…"
                       class="w-full rounded-xl border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-primary focus:ring-primary" />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50" @click="$dispatch('open-modal','productos-mov')">Movimiento</button>
                <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50" @click="$dispatch('open-modal','productos-import')">Import CSV</button>
                <button type="button" class="rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-white hover:bg-primary/90" @click="$dispatch('open-modal','producto-form'); resetForm()">Nuevo</button>
            </div>
        </div>

        <x-table>
            <thead class="bg-slate-50/60">
                <tr class="text-left text-xs font-semibold text-slate-600">
                    <th class="px-4 py-3">SKU</th>
                    <th class="px-4 py-3">Producto</th>
                    <th class="px-4 py-3">Modelo</th>
                    <th class="px-4 py-3">Imagen</th>
                    <th class="px-4 py-3 text-right">Precio</th>
                    <th class="px-4 py-3 text-right">Stock total</th>
                    <th class="px-4 py-3">Actualizado</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="row in pagedRows" :key="row.id">
                    <tr class="border-b border-slate-100 hover:bg-slate-50 cursor-pointer" @click="openDetail(row.id)">
                        <td class="px-4 py-3 font-medium text-slate-900" x-text="row.sku"></td>
                        <td class="px-4 py-3 text-slate-700" x-text="row.nombre"></td>
                        <td class="px-4 py-3 text-slate-700" x-text="row.modelo || '—'"></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <template x-if="row.imagen_url && !imgFailed(row.imagen_url)">
                                    <img :src="fileUrl(row.imagen_url)"
                                         class="h-9 w-12 rounded-lg object-cover ring-1 ring-slate-200 bg-slate-100"
                                         x-on:error="markImgFailed(row.imagen_url)"
                                         alt="" />
                                </template>
                                <template x-if="!row.imagen_url || imgFailed(row.imagen_url)">
                                    <div class="h-9 w-12 rounded-lg bg-slate-100 ring-1 ring-slate-200"></div>
                                </template>
	                                <template x-if="!row.imagen_url || imgFailed(row.imagen_url)">
	                                    <x-badge variant="amber">Sin imagen</x-badge>
	                                </template>
	                            </div>
	                        </td>
                        <td class="px-4 py-3 text-right text-slate-900" x-text="money(row.precio, row.moneda)"></td>
	                        <td class="px-4 py-3 text-right">
	                            <template x-if="stockLevel(row.stock_total).variant === 'rose'">
	                                <x-badge variant="rose"><span x-text="String(row.stock_total ?? 0)"></span></x-badge>
	                            </template>
	                            <template x-if="stockLevel(row.stock_total).variant === 'amber'">
	                                <x-badge variant="amber"><span x-text="String(row.stock_total ?? 0)"></span></x-badge>
	                            </template>
	                            <template x-if="stockLevel(row.stock_total).variant === 'emerald'">
	                                <x-badge variant="emerald"><span x-text="String(row.stock_total ?? 0)"></span></x-badge>
	                            </template>
	                        </td>
                        <td class="px-4 py-3 text-slate-600" x-text="formatUpdated(row.updated_at)"></td>
                    </tr>
                </template>
                <tr x-show="!loading && filteredRows.length === 0">
                    <td class="px-4 py-10 text-center text-slate-500" colspan="7">No hay productos.</td>
                </tr>
            </tbody>
        </x-table>

        <x-pagination page="page" pages="pages"></x-pagination>

        <!-- Detalle -->
        <x-modal name="producto-detalle" maxWidth="3xl">
            <div class="border-b border-slate-200 px-6 py-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900" x-text="detail?.producto?.nombre || 'Producto'"></div>
                        <div class="mt-0.5 text-xs text-slate-500" x-text="detail?.producto?.sku || ''"></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50"
                                @click="fillForm(detail?.producto); $dispatch('open-modal','producto-form')">Editar</button>
                        <button class="rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700"
                                @click="confirmDestroy(detail?.producto?.id)">Eliminar</button>
                        <x-icon-button @click="$dispatch('close-modal','producto-detalle')" aria-label="Cerrar">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </x-icon-button>
                    </div>
                </div>
            </div>
            <div class="p-5 max-h-[86vh] overflow-y-auto space-y-6">
                <template x-if="detailError">
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="detailError"></div>
                </template>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                    <!-- Imagen -->
                    <div class="lg:col-span-4 lg:order-1">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="flex items-center justify-between">
                                <div class="text-xs font-semibold text-slate-900">Imagen</div>
                                <template x-if="detail?.producto?.imagen_url">
                                    <a :href="fileUrl(detail.producto.imagen_url)" target="_blank" class="text-xs font-medium text-primary hover:underline">Abrir</a>
                                </template>
                            </div>
                            <div class="mt-4 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                                <div class="aspect-[4/3] w-full overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 flex items-center justify-center">
                                    <template x-if="detail?.producto?.imagen_url && !imgFailed(detail.producto.imagen_url)">
                                        <img :src="fileUrl(detail.producto.imagen_url)" class="w-full h-full object-contain" x-on:error="markImgFailed(detail.producto.imagen_url)" alt="" />
                                    </template>
                                    <template x-if="!detail?.producto?.imagen_url || imgFailed(detail.producto.imagen_url)">
                                        <div class="p-6 text-center">
                                            <div class="text-sm font-semibold text-slate-900">Sin imagen</div>
                                            <div class="mt-1 text-xs text-slate-500">Sube una imagen o pega una URL en Editar.</div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ficha + Tabs -->
                    <div class="lg:col-span-8 lg:order-2 space-y-4">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-xs font-semibold text-slate-900">Ficha</div>
                                    <div class="mt-1 text-xs text-slate-500" x-text="detail?.producto?.categoria ? `Categoría: ${detail.producto.categoria}` : '—'"></div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <template x-if="detail?.producto?.activo">
                                        <x-badge variant="emerald">Activo</x-badge>
                                    </template>
                                    <template x-if="detail && detail?.producto && !detail?.producto?.activo">
                                        <x-badge variant="slate">Inactivo</x-badge>
                                    </template>
                                </div>
                            </div>
                            <dl class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3 text-sm">
                                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                                    <dt class="text-xs text-slate-500">Precio</dt>
                                    <dd class="mt-1 font-semibold text-slate-900" x-text="money(detail?.producto?.precio, detail?.producto?.moneda)"></dd>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                                    <dt class="text-xs text-slate-500">Costo</dt>
                                    <dd class="mt-1 font-semibold text-slate-900" x-text="money(detail?.producto?.costo, detail?.producto?.moneda)"></dd>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                                    <dt class="text-xs text-slate-500">Stock total</dt>
                                    <dd class="mt-1 font-semibold text-slate-900" x-text="sumStock(detail?.stock || [])"></dd>
                                </div>
                            </dl>
                            <div class="mt-4">
                                <div class="text-xs font-medium text-slate-700">Descripción</div>
                                <div class="mt-1 text-sm text-slate-700 whitespace-pre-wrap" x-text="detail?.producto?.descripcion || '—'"></div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 px-5 py-3">
                                <button type="button" class="rounded-xl px-3 py-1.5 text-sm"
                                        :class="tab==='stock' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                        @click="tab='stock'">Stock por sedes</button>
                                <button type="button" class="rounded-xl px-3 py-1.5 text-sm"
                                        :class="tab==='kardex' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                        @click="tab='kardex'">Kardex</button>
                            </div>

                            <div class="p-5" x-show="tab==='stock'" x-cloak>
                                <x-table>
                                    <thead class="bg-slate-50/60">
                                        <tr class="text-left text-xs font-semibold text-slate-600">
                                            <th class="px-4 py-3">Sede</th>
                                            <th class="px-4 py-3 text-right">Stock</th>
                                            <th class="px-4 py-3 text-right">Min</th>
                                            <th class="px-4 py-3">Ubicación</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="s in (detail?.stock || [])" :key="s.id">
                                            <tr class="border-b border-slate-100 hover:bg-slate-50">
                                                <td class="px-4 py-3">
                                                    <div class="font-medium text-slate-900" x-text="s.sucursal_nombre || '—'"></div>
                                                    <div class="text-xs text-slate-500" x-text="s.sucursal_codigo || ''"></div>
                                                </td>
                                                <td class="px-4 py-3 text-right text-slate-900" x-text="s.stock ?? 0"></td>
                                                <td class="px-4 py-3 text-right text-slate-900" x-text="s.stock_min ?? 0"></td>
                                                <td class="px-4 py-3 text-slate-700" x-text="s.ubicacion || '—'"></td>
                                            </tr>
                                        </template>
                                        <tr x-show="(detail?.stock || []).length === 0">
                                            <td class="px-4 py-10 text-center text-slate-500" colspan="4">Sin stock por sedes.</td>
                                        </tr>
                                    </tbody>
                                </x-table>
                            </div>

                            <div class="p-5" x-show="tab==='kardex'" x-cloak>
                                <x-table>
                                    <thead class="bg-slate-50/60">
                                        <tr class="text-left text-xs font-semibold text-slate-600">
                                            <th class="px-4 py-3">Fecha</th>
                                            <th class="px-4 py-3">Tipo</th>
                                            <th class="px-4 py-3 text-right">Cant</th>
                                            <th class="px-4 py-3">Origen</th>
                                            <th class="px-4 py-3">Destino</th>
                                            <th class="px-4 py-3">Motivo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="k in (detail?.kardex || [])" :key="k.id">
                                            <tr class="border-b border-slate-100 hover:bg-slate-50">
                                                <td class="px-4 py-3 text-slate-700" x-text="k.created_at || '—'"></td>
                                                <td class="px-4 py-3 text-slate-700" x-text="k.tipo || '—'"></td>
                                                <td class="px-4 py-3 text-right text-slate-900" x-text="k.cantidad ?? 0"></td>
                                                <td class="px-4 py-3 text-slate-700" x-text="k.sucursal_origen_nombre || '—'"></td>
                                                <td class="px-4 py-3 text-slate-700" x-text="k.sucursal_destino_nombre || '—'"></td>
                                                <td class="px-4 py-3 text-slate-700" x-text="k.motivo || '—'"></td>
                                            </tr>
                                        </template>
                                        <tr x-show="(detail?.kardex || []).length === 0">
                                            <td class="px-4 py-10 text-center text-slate-500" colspan="6">Sin movimientos.</td>
                                        </tr>
                                    </tbody>
                                </x-table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-modal>

        <!-- Create/Edit -->
        <x-modal name="producto-form" maxWidth="3xl" focusable>
            <form class="divide-y divide-slate-200" @submit.prevent="submitForm()">
                <div class="px-6 py-4 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900" x-text="form.id ? 'Editar producto' : 'Nuevo producto'"></div>
                    </div>
                    <x-icon-button @click="$dispatch('close-modal','producto-form')" aria-label="Cerrar">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </x-icon-button>
                </div>
                <div class="px-6 py-5">
                    <template x-if="formError">
                        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="formError"></div>
                    </template>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-medium text-slate-700">SKU</label>
                            <input x-model="form.sku" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" required />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Nombre</label>
                            <input x-model="form.nombre" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" required />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Categoría</label>
                            <input x-model="form.categoria" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Modelo / Código proveedor</label>
                            <input x-model="form.modelo" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Precio</label>
                            <input x-model="form.precio" type="number" step="0.01" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Costo</label>
                            <input x-model="form.costo" type="number" step="0.01" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Moneda</label>
                            <select x-model="form.moneda" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                <option value="PEN">PEN</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary" x-model="form.activo" />
                                Activo
                            </label>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Imagen URL (opcional)</label>
                            <input x-model="form.imagen_url" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" placeholder="https://..." />
                            <div class="mt-2 flex items-center justify-between gap-3">
                                <label class="flex items-center gap-2 text-xs text-slate-600">
                                    <input type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary" x-model="form.remove_imagen" />
                                    Quitar imagen
                                </label>
                                <div class="text-xs text-slate-500">o subir archivo</div>
                            </div>
                            <input type="file" accept="image/jpeg,image/png,image/webp"
                                   x-ref="imagenFile"
                                   class="mt-2 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Descripción / Especificaciones</label>
                            <textarea x-model="form.descripcion" rows="3" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"></textarea>
                        </div>
                        <div class="sm:col-span-2" x-show="!form.id" x-cloak>
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-xs font-medium text-slate-700">Stock inicial por sucursal (opcional)</div>
                                    <div class="mt-0.5 text-xs text-slate-500">Se registrará como movimientos ENTRADA.</div>
                                </div>
                                <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs hover:bg-slate-50" @click="addStockRow()">Agregar</button>
                            </div>
                            <div class="mt-3 space-y-2">
                                <template x-for="(r, i) in (form.stock_inicial || [])" :key="i">
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-12">
                                        <div class="sm:col-span-5">
                                            <label class="sr-only">Sucursal</label>
                                            <template x-if="sucursales.length">
                                                <select x-model="r.sucursal_id" class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                                    <option value="">Sucursal…</option>
                                                    <template x-for="s in sucursales" :key="s.id">
                                                        <option :value="s.id" x-text="`${s.codigo} — ${s.nombre}`"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            <template x-if="!sucursales.length">
                                                <input x-model="r.sucursal_id" type="number" min="1" class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" placeholder="Sucursal ID" />
                                            </template>
                                        </div>
                                        <div class="sm:col-span-3">
                                            <label class="sr-only">Cantidad</label>
                                            <input x-model="r.cantidad" type="number" min="1" class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" placeholder="Cant." />
                                        </div>
                                        <div class="sm:col-span-3">
                                            <label class="sr-only">Motivo</label>
                                            <input x-model="r.motivo" class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" placeholder="Motivo" />
                                        </div>
                                        <div class="sm:col-span-1 flex items-center justify-end">
                                            <button type="button" class="rounded-lg px-2 py-2 text-slate-500 hover:bg-slate-100" @click="removeStockRow(i)" aria-label="Quitar">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="(form.stock_inicial || []).length === 0">
                                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">
                                        Sin stock inicial.
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50" @click="$dispatch('close-modal','producto-form')">Cancelar</button>
                    <button class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90" :disabled="saving" x-text="saving ? 'Guardando…' : 'Guardar'"></button>
                </div>
            </form>
        </x-modal>

        <!-- Movimiento -->
        <x-modal name="productos-mov" maxWidth="2xl" focusable>
            <form class="divide-y divide-slate-200" @submit.prevent="submitMovimiento()">
                <div class="px-6 py-4 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Movimiento de stock</div>
                        <div class="mt-0.5 text-xs text-slate-500">Registra uno o varios movimientos en una sola operación.</div>
                    </div>
                    <x-icon-button @click="$dispatch('close-modal','productos-mov')" aria-label="Cerrar">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </x-icon-button>
                </div>
                <div class="px-6 py-5 space-y-3">
                    <template x-if="movError">
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="movError"></div>
                    </template>

                    <div class="flex items-center justify-between gap-3">
                        <div class="text-xs font-medium text-slate-700">Items</div>
                        <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs hover:bg-slate-50" @click="addMovRow()">Agregar producto</button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(r, i) in movRows" :key="i">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="text-xs font-semibold text-slate-900" x-text="`Producto #${i+1}`"></div>
                                    <button type="button" class="rounded-lg px-2 py-2 text-slate-500 hover:bg-slate-100" @click="removeMovRow(i)" aria-label="Quitar">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <label class="text-xs font-medium text-slate-700">Producto</label>
                                        <select x-model="r.producto_id" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" required>
                                            <option value="">Selecciona…</option>
                                            <template x-for="p in rows" :key="p.id">
                                                <option :value="p.id" x-text="`${p.sku} — ${p.nombre}`"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between">
                                            <label class="text-xs font-medium text-slate-700">Tipo</label>
                                            <div class="relative" x-data="{ open:false }">
                                                <button type="button"
                                                        class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs text-slate-600 hover:bg-slate-100"
                                                        @mouseenter="open=true" @mouseleave="open=false"
                                                        @focus="open=true" @blur="open=false"
                                                        aria-label="Ayuda de tipos">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                                                    </svg>
                                                    Ayuda
                                                </button>
                                                <div x-show="open" x-cloak
                                                     class="absolute right-0 z-10 mt-2 w-80 rounded-2xl border border-slate-200 bg-white p-4 text-xs text-slate-700 shadow-xl ring-1 ring-slate-900/5">
                                                    <div class="font-semibold text-slate-900">Tipos de movimiento</div>
                                                    <ul class="mt-2 space-y-1.5">
                                                        <li><span class="font-medium">ENTRADA:</span> ingresa stock a una sede (requiere destino).</li>
                                                        <li><span class="font-medium">SALIDA:</span> retira stock de una sede (requiere origen, no permite negativo).</li>
                                                        <li><span class="font-medium">TRANSFER:</span> mueve stock entre sedes (requiere origen y destino distintos).</li>
                                                        <li><span class="font-medium">AJUSTE:</span> corrección manual (se aplica sobre origen, no permite negativo).</li>
                                                        <li><span class="font-medium">VENTA:</span> salida por venta (requiere origen, no permite negativo).</li>
                                                        <li><span class="font-medium">DEVOLUCIÓN:</span> retorno de stock a sede (requiere destino).</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <select x-model="r.tipo" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" required>
                                            <option value="ENTRADA">ENTRADA</option>
                                            <option value="SALIDA">SALIDA</option>
                                            <option value="TRANSFER">TRANSFER</option>
                                            <option value="AJUSTE">AJUSTE</option>
                                            <option value="VENTA">VENTA</option>
                                            <option value="DEVOLUCION">DEVOLUCION</option>
                                        </select>
                                        <div class="mt-2 text-xs text-slate-500" x-text="tipoHint(r)"></div>
                                    </div>

                                    <div>
                                        <label class="text-xs font-medium text-slate-700">Cantidad</label>
                                        <input x-model="r.cantidad" type="number" min="1" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" required />
                                    </div>

                                    <div x-show="requiresOrigen(r)" x-cloak>
                                        <label class="text-xs font-medium text-slate-700">Sucursal origen</label>
                                        <template x-if="sucursales.length">
                                            <select x-model="r.sucursal_origen" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                                <option value="">Selecciona…</option>
                                                <template x-for="s in sucursales" :key="s.id">
                                                    <option :value="s.id" x-text="`${s.codigo} — ${s.nombre}`"></option>
                                                </template>
                                            </select>
                                        </template>
                                        <template x-if="!sucursales.length">
                                            <input x-model="r.sucursal_origen" type="number" min="1" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" placeholder="ID" />
                                        </template>
                                    </div>

                                    <div x-show="requiresDestino(r)" x-cloak>
                                        <label class="text-xs font-medium text-slate-700">Sucursal destino</label>
                                        <template x-if="sucursales.length">
                                            <select x-model="r.sucursal_destino" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                                <option value="">Selecciona…</option>
                                                <template x-for="s in sucursales" :key="s.id">
                                                    <option :value="s.id" x-text="`${s.codigo} — ${s.nombre}`"></option>
                                                </template>
                                            </select>
                                        </template>
                                        <template x-if="!sucursales.length">
                                            <input x-model="r.sucursal_destino" type="number" min="1" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" placeholder="ID" />
                                        </template>
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label class="text-xs font-medium text-slate-700">Motivo (opcional)</label>
                                        <input x-model="r.motivo" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="px-6 py-4 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50" @click="$dispatch('close-modal','productos-mov')">Cancelar</button>
                    <button class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90" :disabled="savingMov" x-text="savingMov ? 'Guardando…' : 'Registrar'"></button>
                </div>
            </form>
        </x-modal>

        <!-- Import -->
        <x-modal name="productos-import" maxWidth="2xl" focusable>
            <form class="divide-y divide-slate-200" @submit.prevent="submitImport()">
                <div class="px-6 py-4 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Importar productos (CSV)</div>
                        <div class="mt-0.5 text-xs text-slate-500">Columnas: sku,nombre,precio</div>
                    </div>
                    <x-icon-button @click="$dispatch('close-modal','productos-import')" aria-label="Cerrar">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </x-icon-button>
                </div>
                <div class="px-6 py-5 space-y-3">
                    <template x-if="importError">
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="importError"></div>
                    </template>
                    <input type="file" accept=".csv,text/csv,text/plain" x-ref="importFile" class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm" required />
                </div>
                <div class="px-6 py-4 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50" @click="$dispatch('close-modal','productos-import')">Cancelar</button>
                    <button class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90" :disabled="savingImport" x-text="savingImport ? 'Importando…' : 'Importar'"></button>
                </div>
            </form>
        </x-modal>
    </div>

    <script>
        function productosPage({ urls }) {
            return {
                urls,
                q: '',
                rows: [],
                loading: false,
                page: 0,
                perPage: 25,

                detail: null,
                detailError: '',
                tab: 'stock',
                imgFail: {},

                form: { id: null, sku: '', nombre: '', categoria: '', modelo: '', precio: 0, costo: 0, moneda: 'PEN', activo: true, imagen_url: '', remove_imagen: false, descripcion: '', stock_inicial: [] },
                formError: '',
                saving: false,

                sucursales: [],
                movRows: [],
                movError: '',
                savingMov: false,

                importError: '',
                savingImport: false,

                async init() {
                    await this.reload();
                    this.loadSucursales();
                    this.resetMovRows();
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

                imgFailed(url) {
                    const k = String(url || '').trim();
                    if (!k) return false;
                    return this.imgFail[k] === true;
                },

                markImgFailed(url) {
                    const k = String(url || '').trim();
                    if (!k) return;
                    this.imgFail[k] = true;
                },

	                get filteredRows() {
	                    const q = (this.q || '').trim().toLowerCase();
	                    if (!q) return this.rows;
	                    return this.rows.filter((r) => this.rowSearchText(r).includes(q));
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

                get pages() {
                    return Math.max(1, Math.ceil(this.filteredRows.length / this.perPage));
                },

                get pagedRows() {
                    const start = this.page * this.perPage;
                    return this.filteredRows.slice(start, start + this.perPage);
                },

                resetForm() {
                    this.formError = '';
                    this.form = { id: null, sku: '', nombre: '', categoria: '', modelo: '', precio: 0, costo: 0, moneda: 'PEN', activo: true, imagen_url: '', remove_imagen: false, descripcion: '', stock_inicial: [] };
                    if (this.$refs.imagenFile) this.$refs.imagenFile.value = '';
                },

                fillForm(p) {
                    this.formError = '';
                    if (!p) return this.resetForm();
                    this.form = {
                        id: p.id || null,
                        sku: p.sku || '',
                        nombre: p.nombre || '',
                        categoria: p.categoria || '',
                        modelo: p.modelo || '',
                        precio: p.precio || 0,
                        costo: p.costo || 0,
                        moneda: p.moneda || 'PEN',
                        activo: (p.activo === false || p.activo === 0 || p.activo === '0') ? false : true,
                        imagen_url: p.imagen_url || '',
                        remove_imagen: false,
                        descripcion: p.descripcion || '',
                        stock_inicial: [],
                    };
                    if (this.$refs.imagenFile) this.$refs.imagenFile.value = '';
                },

                async openDetail(id) {
                    this.detailError = '';
                    this.detail = null;
                    this.tab = 'stock';
                    this.$dispatch('open-modal', 'producto-detalle');
                    try {
                        const res = await window.axios.get(this.urls.show(id), { headers: { 'Accept': 'application/json' } });
                        this.detail = res.data?.data || null;
                        if (res.data?.ok !== true) this.detailError = res.data?.error || 'No se pudo cargar.';
                    } catch (e) {
                        this.detailError = e?.response?.data?.error || e?.message || 'Error';
                    }
                },

                async submitForm() {
                    this.formError = '';
                    this.saving = true;
                    try {
                        const fd = new FormData();
                        fd.append('sku', this.form.sku || '');
                        fd.append('nombre', this.form.nombre || '');
                        fd.append('categoria', this.form.categoria || '');
                        fd.append('modelo', this.form.modelo || '');
                        fd.append('precio', String(this.form.precio ?? 0));
                        fd.append('costo', String(this.form.costo ?? 0));
                        fd.append('moneda', this.form.moneda || 'PEN');
                        fd.append('activo', this.form.activo ? '1' : '0');
                        fd.append('imagen_url', this.form.imagen_url || '');
                        if (this.form.remove_imagen) fd.append('remove_imagen', '1');
                        fd.append('descripcion', this.form.descripcion || '');
                        if (!this.form.id && Array.isArray(this.form.stock_inicial) && this.form.stock_inicial.length > 0) {
                            const cleaned = this.form.stock_inicial
                                .filter(r => r && r.sucursal_id && r.cantidad)
                                .map(r => ({
                                    sucursal_id: Number(r.sucursal_id),
                                    cantidad: Number(r.cantidad),
                                    motivo: r.motivo ? String(r.motivo) : null,
                                }))
                                .filter(r => r.sucursal_id > 0 && r.cantidad > 0);
                            if (cleaned.length > 0) {
                                fd.append('stock_inicial_json', JSON.stringify(cleaned));
                            }
                        }

                        const file = this.$refs.imagenFile?.files?.[0];
                        if (file) {
                            fd.append('imagen_file', file);
                        }
                        const url = this.form.id ? this.urls.update(this.form.id) : this.urls.create;
                        const res = await window.axios.post(url, fd, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.formError = res.data?.error || 'No se pudo guardar.';
                            return;
                        }
                        this.$dispatch('close-modal', 'producto-form');
                        await this.reload();
                        if (this.form.id) {
                            await this.refreshDetail(this.form.id);
                        }
                    } catch (e) {
                        this.formError = e?.response?.data?.error || e?.message || 'Error';
                    } finally {
                        this.saving = false;
                    }
                },

                async refreshDetail(id) {
                    const currentId = Number(id || 0);
                    if (!currentId) return;
                    try {
                        const res = await window.axios.get(this.urls.show(currentId), { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok === true) {
                            this.detail = res.data?.data || this.detail;
                        }
                    } catch {
                        // best-effort
                    }
                },

                addStockRow() {
                    if (!Array.isArray(this.form.stock_inicial)) this.form.stock_inicial = [];
                    this.form.stock_inicial.push({ sucursal_id: '', cantidad: 1, motivo: '' });
                },

                removeStockRow(i) {
                    if (!Array.isArray(this.form.stock_inicial)) return;
                    this.form.stock_inicial.splice(i, 1);
                },

                async confirmDestroy(id) {
                    if (!id) return;
                    if (!await window.GCDialog.confirm({ title: 'Eliminar producto', message: 'Se eliminará el producto seleccionado. Esta acción no se puede deshacer.', confirmText: 'Eliminar', tone: 'danger' })) return;
                    try {
                        const res = await window.axios.post(this.urls.destroy(id), {}, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            window.GCToast?.error('No se pudo eliminar', res.data?.error || 'Error');
                            return;
                        }
                        this.$dispatch('close-modal', 'producto-detalle');
                        await this.reload();
                        window.GCToast?.success('Producto eliminado', 'Se eliminó el producto.');
                    } catch (e) {
                        window.GCToast?.error('Error', e?.response?.data?.error || e?.message || 'Error');
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

                resetMovRows() {
                    this.movError = '';
                    this.movRows = [{
                        producto_id: '',
                        tipo: 'ENTRADA',
                        cantidad: 1,
                        sucursal_origen: '',
                        sucursal_destino: '',
                        motivo: '',
                    }];
                },

                addMovRow() {
                    if (!Array.isArray(this.movRows)) this.movRows = [];
                    this.movRows.push({
                        producto_id: '',
                        tipo: 'ENTRADA',
                        cantidad: 1,
                        sucursal_origen: '',
                        sucursal_destino: '',
                        motivo: '',
                    });
                },

                removeMovRow(i) {
                    if (!Array.isArray(this.movRows)) return;
                    this.movRows.splice(i, 1);
                    if (this.movRows.length === 0) this.resetMovRows();
                },

                requiresOrigen(row) {
                    const t = String(row?.tipo || '').toUpperCase();
                    return ['SALIDA', 'TRANSFER', 'AJUSTE', 'VENTA'].includes(t);
                },

                requiresDestino(row) {
                    const t = String(row?.tipo || '').toUpperCase();
                    return ['ENTRADA', 'TRANSFER', 'DEVOLUCION'].includes(t);
                },

                tipoHint(row) {
                    const t = String(row?.tipo || '').toUpperCase();
                    return ({
                        'ENTRADA': 'Suma stock en la sede destino (ej.: compra, ingreso).',
                        'SALIDA': 'Resta stock en la sede origen (ej.: consumo, salida manual).',
                        'TRANSFER': 'Resta en origen y suma en destino (mismo producto entre sedes).',
                        'AJUSTE': 'Corrección manual sobre la sede origen (no permite negativo).',
                        'VENTA': 'Salida por venta: resta stock en la sede origen (no permite negativo).',
                        'DEVOLUCION': 'Retorno: suma stock en la sede destino (ej.: devolución de cliente).',
                    })[t] || 'Selecciona un tipo para ver la explicación.';
                },

                async submitMovimiento() {
                    this.movError = '';
                    this.savingMov = true;
                    try {
                        const rows = Array.isArray(this.movRows) ? this.movRows : [];
                        if (rows.length === 0) {
                            this.movError = 'Agrega al menos un producto.';
                            return;
                        }

                        for (let i = 0; i < rows.length; i++) {
                            const r = rows[i] || {};
                            const tipo = String(r.tipo || '').toUpperCase();
                            const productoId = Number(r.producto_id || 0);
                            const cantidad = Number(r.cantidad || 0);
                            const o = r.sucursal_origen ? Number(r.sucursal_origen) : null;
                            const d = r.sucursal_destino ? Number(r.sucursal_destino) : null;

                            if (!productoId || productoId <= 0) { this.movError = `Fila ${i + 1}: selecciona un producto.`; return; }
                            if (!tipo) { this.movError = `Fila ${i + 1}: selecciona un tipo.`; return; }
                            if (!cantidad || cantidad <= 0) { this.movError = `Fila ${i + 1}: cantidad inválida.`; return; }

                            if (tipo === 'TRANSFER' && o && d && o === d) {
                                this.movError = `Fila ${i + 1}: la sucursal origen y destino no pueden ser la misma.`;
                                return;
                            }

                            const res = await window.axios.post(this.urls.movimiento, {
                                producto_id: productoId,
                                tipo,
                                cantidad,
                                motivo: r.motivo ? String(r.motivo) : null,
                                sucursal_origen: o,
                                sucursal_destino: d,
                            }, { headers: { 'Accept': 'application/json' } });
                            if (res.data?.ok !== true) {
                                this.movError = `Fila ${i + 1}: ${res.data?.error || 'No se pudo registrar.'}`;
                                return;
                            }
                        }

                        this.$dispatch('close-modal', 'productos-mov');
                        await this.reload();
                    } catch (e) {
                        this.movError = e?.response?.data?.error || e?.message || 'Error';
                    } finally {
                        this.savingMov = false;
                    }
                },

                async submitImport() {
                    this.importError = '';
                    this.savingImport = true;
                    try {
                        const file = this.$refs.importFile?.files?.[0];
                        if (!file) { this.importError = 'Selecciona un archivo.'; return; }
                        const fd = new FormData();
                        fd.append('file', file);
                        const res = await window.axios.post(this.urls.import, fd, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.importError = res.data?.error || 'No se pudo importar.';
                            return;
                        }
                        this.$dispatch('close-modal', 'productos-import');
                        await this.reload();
                    } catch (e) {
                        this.importError = e?.response?.data?.error || e?.message || 'Error';
                    } finally {
                        this.savingImport = false;
                    }
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

                sumStock(stockRows) {
                    const rows = Array.isArray(stockRows) ? stockRows : [];
                    return rows.reduce((acc, r) => acc + Number(r?.stock ?? 0), 0);
                },

                fileUrl(v) {
                    if (!v) return null;
                    const s = String(v).trim();
                    if (s.startsWith('/')) return s;
                    if (s.startsWith('http://') || s.startsWith('https://')) {
                        try {
                            const u = new URL(s);
                            return u.pathname + (u.search || '');
                        } catch {
                            return s;
                        }
                    }
                    return s;
                },

                formatUpdated(v) {
                    const s = (v === null || v === undefined) ? '' : String(v);
                    // soporta "YYYY-MM-DD HH:MM:SS(.micro)" o ISO
                    const m = s.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
                    if (m) {
                        return `${m[3]}/${m[2]}/${m[1]} ${m[4]}:${m[5]}`;
                    }
                    try {
                        const d = new Date(s);
                        if (!Number.isNaN(d.getTime())) {
                            const dd = String(d.getDate()).padStart(2, '0');
                            const mm = String(d.getMonth() + 1).padStart(2, '0');
                            const yy = d.getFullYear();
                            const hh = String(d.getHours()).padStart(2, '0');
                            const mi = String(d.getMinutes()).padStart(2, '0');
                            return `${dd}/${mm}/${yy} ${hh}:${mi}`;
                        }
                    } catch {}
                    return s || '—';
                },

                stockLevel(v) {
                    const n = Number(v ?? 0);
                    if (n <= 1) return { variant: 'rose' };
                    if (n <= 5) return { variant: 'amber' };
                    return { variant: 'emerald' };
                },
            }
        }
    </script>
</x-app-layout>
