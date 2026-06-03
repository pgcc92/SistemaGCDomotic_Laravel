<x-app-layout>
    <x-slot name="header">
        <div class="text-lg font-semibold text-slate-900">Clientes</div>
    </x-slot>

    <div
        x-data="clientesPage({
            urls: {
                data: '{{ route('clientes.data') }}',
                show: (id) => `/clientes/${id}`,
                create: '{{ route('clientes.store') }}',
                update: (id) => `/clientes/${id}/editar`,
                destroy: (id) => `/clientes/${id}/eliminar`,
            }
        })"
        x-init="init()"
        class="space-y-6"
    >
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="gc-card p-5 bg-gradient-to-br from-emerald-50 to-white">
                <div class="text-xs font-semibold text-emerald-700">Clientes activos</div>
                <div class="mt-2 text-2xl font-semibold text-slate-900" x-text="rows.length"></div>
                <div class="mt-1 text-xs text-slate-500">Registros visibles.</div>
            </div>
            <div class="gc-card p-5 bg-gradient-to-br from-violet-50 to-white">
                <div class="text-xs font-semibold text-violet-700">Empresas</div>
                <div class="mt-2 text-2xl font-semibold text-slate-900" x-text="empresaCount"></div>
                <div class="mt-1 text-xs text-slate-500">RUC o razón social.</div>
            </div>
            <div class="gc-card p-5 bg-gradient-to-br from-sky-50 to-white">
                <div class="text-xs font-semibold text-sky-700">Documentados</div>
                <div class="mt-2 text-2xl font-semibold text-slate-900" x-text="docCount"></div>
                <div class="mt-1 text-xs text-slate-500">Con tipo y número.</div>
            </div>
            <div class="gc-card p-5 bg-gradient-to-br from-amber-50 to-white">
                <div class="text-xs font-semibold text-amber-700">Con email</div>
                <div class="mt-2 text-2xl font-semibold text-slate-900" x-text="emailCount"></div>
                <div class="mt-1 text-xs text-slate-500">Contactabilidad adicional.</div>
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
                       placeholder="Buscar por cualquier dato del cliente…"
                       class="w-full rounded-xl border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-primary focus:ring-primary" />
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-white hover:bg-primary/90"
                        @click="$dispatch('open-modal','cliente-form'); $dispatch('cliente-new')">
                    Nuevo cliente
                </button>
            </div>
        </div>

        <div class="gc-card p-5">
            <x-table class="border-0 shadow-none">
                <thead class="bg-slate-50/60">
                    <tr class="text-left text-xs font-semibold text-slate-600">
                        <th class="px-4 py-3">Teléfono</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Documento</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Actualizado</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in pagedRows" :key="row.id">
                        <tr class="border-b border-slate-100 hover:bg-slate-50 cursor-pointer"
                            @click="openDetail(row.id)">
                            <td class="px-4 py-3 font-medium text-slate-900" x-text="row.telefono"></td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-slate-900" x-text="row.nombre || '—'"></div>
                                <template x-if="row.razon_social">
                                    <div class="mt-1">
                                        <span class="inline-flex items-center rounded-full bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200"
                                              x-text="row.razon_social"></span>
                                    </div>
                                </template>
                            </td>
                            <td class="px-4 py-3">
                                <template x-if="row.tipo_documento && row.numero_documento">
                                    <div class="inline-flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset"
                                              :class="(row.tipo_documento || '').toUpperCase() === 'RUC' ? 'bg-violet-50 text-violet-700 ring-violet-200' : 'bg-sky-50 text-sky-700 ring-sky-200'"
                                              x-text="row.tipo_documento"></span>
                                        <span class="text-sm text-slate-700" x-text="row.numero_documento"></span>
                                    </div>
                                </template>
                                <template x-if="!(row.tipo_documento && row.numero_documento)">
                                    <span class="text-sm text-slate-400">—</span>
                                </template>
                            </td>
                            <td class="px-4 py-3 text-slate-700" x-text="row.email || '—'"></td>
                            <td class="px-4 py-3 text-slate-600" x-text="shortDateTime(row.updated_at)"></td>
                        </tr>
                    </template>
                    <tr x-show="!loading && filteredRows.length === 0">
                        <td class="px-4 py-10 text-center text-slate-500" colspan="5">No hay clientes.</td>
                    </tr>
                </tbody>
            </x-table>
        </div>

        <x-pagination page="page" pages="pages"></x-pagination>

        <!-- Detalle -->
        <x-modal name="cliente-detalle" maxWidth="4xl">
            <div class="border-b border-slate-200 px-6 py-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900" x-text="detail?.cliente?.nombre || 'Cliente'"></div>
	                        <template x-if="detail?.cliente?.razon_social">
	                            <div class="mt-1">
	                                <span class="inline-flex items-center rounded-full bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200"
	                                      x-text="detail?.cliente?.razon_social"></span>
	                            </div>
	                        </template>
                        <div class="mt-0.5 text-xs text-slate-500" x-text="detail?.cliente?.telefono || ''"></div>
                    </div>
                    <div class="flex items-center gap-2">
	                        <template x-if="detail?.cliente?.tipo_documento && detail?.cliente?.numero_documento">
	                            <div class="hidden sm:flex items-center gap-2 mr-1">
	                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset"
	                                      :class="(detail?.cliente?.tipo_documento || '').toUpperCase() === 'RUC' ? 'bg-violet-50 text-violet-700 ring-violet-200' : 'bg-sky-50 text-sky-700 ring-sky-200'"
	                                      x-text="detail?.cliente?.tipo_documento"></span>
	                                <span class="text-sm text-slate-700" x-text="detail?.cliente?.numero_documento"></span>
	                            </div>
	                        </template>
                        <button class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50"
                                @click="$dispatch('open-modal','cliente-form'); $dispatch('cliente-edit', { cliente: detail?.cliente })">Editar</button>
                        <button class="rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700"
                                @click="confirmDestroy(detail?.cliente?.id)">Eliminar</button>
                        <x-icon-button @click="$dispatch('close-modal','cliente-detalle')" aria-label="Cerrar">
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
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="text-xs font-semibold text-slate-900">Ficha</div>
                            <dl class="mt-3 space-y-3 text-sm">
                                <div class="grid grid-cols-1 gap-1">
                                    <dt class="text-xs font-medium text-slate-500">Email</dt>
                                    <dd class="text-slate-900" x-text="detail?.cliente?.email || '—'"></dd>
                                </div>
                                <div class="grid grid-cols-1 gap-1">
                                    <dt class="text-xs font-medium text-slate-500">Dirección</dt>
                                    <dd class="text-slate-900" x-text="detail?.cliente?.direccion || '—'"></dd>
                                </div>
                                <div class="grid grid-cols-1 gap-1">
                                    <dt class="text-xs font-medium text-slate-500">Documento</dt>
                                    <dd class="text-slate-900" x-text="(detail?.cliente?.tipo_documento && detail?.cliente?.numero_documento) ? (detail?.cliente?.tipo_documento+' '+detail?.cliente?.numero_documento) : '—'"></dd>
                                </div>
                                <template x-if="detail?.cliente?.razon_social">
                                    <div class="grid grid-cols-1 gap-1">
                                        <dt class="text-xs font-medium text-slate-500">Razón social</dt>
                                        <dd>
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700"
                                                  x-text="detail?.cliente?.razon_social"></span>
                                        </dd>
                                    </div>
                                </template>
                            </dl>
                        </div>
                    </div>

                    <div class="lg:col-span-8">
                        <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-3">
                                <div class="text-xs font-semibold text-slate-900">Actividad</div>
                                <div class="flex items-center gap-1 rounded-xl bg-slate-50 p-1">
                                    <button type="button"
                                            class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                            :class="detailTab === 'tickets' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-600 hover:text-slate-900'"
                                            @click="detailTab = 'tickets'">
                                        Tickets
                                        <span class="ml-1 text-[10px] font-semibold text-slate-500" x-text="(detail?.tickets || []).length"></span>
                                    </button>
                                    <button type="button"
                                            class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                            :class="detailTab === 'ventas' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-600 hover:text-slate-900'"
                                            @click="detailTab = 'ventas'">
                                        Ventas
                                        <span class="ml-1 text-[10px] font-semibold text-slate-500" x-text="(detail?.ventas || []).length"></span>
                                    </button>
                                </div>
                            </div>

                            <div class="p-5">
                                <!-- Tickets -->
                                <div x-show="detailTab === 'tickets'" x-transition.opacity.duration.150ms>
                                    <div class="overflow-hidden rounded-xl border border-slate-200">
                                        <table class="min-w-full divide-y divide-slate-200">
                                            <thead class="bg-slate-50">
                                                <tr class="text-left text-[11px] font-semibold tracking-wide text-slate-600">
                                                    <th class="px-3 py-2">Ticket</th>
                                                    <th class="px-3 py-2">Estado</th>
                                                    <th class="px-3 py-2">Categoría</th>
                                                    <th class="px-3 py-2">Actualizado</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 bg-white">
                                                <template x-for="t in (detail?.tickets || [])" :key="t.id || t.ticket_id">
                                                    <tr class="hover:bg-slate-50">
                                                        <td class="px-3 py-2 text-sm font-medium text-slate-900" x-text="t.ticket_id || t.id || '—'"></td>
                                                        <td class="px-3 py-2 text-sm">
                                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700"
                                                                  x-text="t.estado || '—'"></span>
                                                        </td>
                                                        <td class="px-3 py-2 text-sm text-slate-700" x-text="t.categoria || t.categoria_problema || '—'"></td>
                                                        <td class="px-3 py-2 text-sm text-slate-600" x-text="shortDateTime(t.updated_at || t.created_at)"></td>
                                                    </tr>
                                                </template>
                                                <tr x-show="(detail?.tickets || []).length === 0">
                                                    <td colspan="4" class="px-3 py-10 text-center text-sm text-slate-500">No hay tickets recientes.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Ventas -->
                                <div x-show="detailTab === 'ventas'" x-transition.opacity.duration.150ms>
                                    <div class="overflow-hidden rounded-xl border border-slate-200">
                                        <table class="min-w-full divide-y divide-slate-200">
                                            <thead class="bg-slate-50">
                                                <tr class="text-left text-[11px] font-semibold tracking-wide text-slate-600">
                                                    <th class="px-3 py-2">Código</th>
                                                    <th class="px-3 py-2">Documento</th>
                                                    <th class="px-3 py-2">Estado</th>
                                                    <th class="px-3 py-2 text-right">Total</th>
                                                    <th class="px-3 py-2">Fecha</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 bg-white">
                                                <template x-for="v in (detail?.ventas || [])" :key="v.id || v.venta_codigo">
                                                    <tr class="hover:bg-slate-50">
                                                        <td class="px-3 py-2 text-sm font-medium text-slate-900" x-text="v.venta_codigo || v.id || '—'"></td>
                                                        <td class="px-3 py-2 text-sm text-slate-700" x-text="ventaDoc(v)"></td>
                                                        <td class="px-3 py-2 text-sm">
                                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700"
                                                                  x-text="v.estado || '—'"></span>
                                                        </td>
                                                        <td class="px-3 py-2 text-sm text-right text-slate-900" x-text="money(v.total, v.moneda)"></td>
                                                        <td class="px-3 py-2 text-sm text-slate-600" x-text="shortDateTime(v.fecha_venta || v.created_at)"></td>
                                                    </tr>
                                                </template>
                                                <tr x-show="(detail?.ventas || []).length === 0">
                                                    <td colspan="5" class="px-3 py-10 text-center text-sm text-slate-500">No hay ventas registradas.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-modal>

        <!-- Create/Edit -->
        <x-modal name="cliente-form" maxWidth="3xl" focusable>
            <form class="divide-y divide-slate-200" @submit.prevent="submitForm()">
                <div class="px-6 py-4 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900" x-text="form.id ? 'Editar cliente' : 'Nuevo cliente'"></div>
                        <div class="mt-0.5 text-xs text-slate-500">Crear/editar sin salir de la lista</div>
                    </div>
                    <x-icon-button @click="$dispatch('close-modal','cliente-form')" aria-label="Cerrar">
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
                            <label class="text-xs font-medium text-slate-700">Teléfono</label>
                            <input x-model="form.telefono" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" required />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Nombre</label>
                            <input x-model="form.nombre" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Dirección (opcional)</label>
                            <textarea x-model="form.direccion" rows="3" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                                <div class="text-xs font-semibold text-slate-900">Compra como empresa (opcional)</div>
                                <div class="mt-1 text-xs text-slate-500">Completa estos datos solo si el cliente requiere factura/boleta con razón social.</div>
                                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="text-xs font-medium text-slate-700">Tipo doc</label>
                                        <select x-model="form.tipo_documento" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                            <option value="">—</option>
                                            <option value="DNI">DNI</option>
                                            <option value="RUC">RUC</option>
                                            <option value="CE">CE</option>
                                            <option value="PAS">PAS</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-slate-700">N° documento</label>
                                        <input x-model="form.numero_documento" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="text-xs font-medium text-slate-700">Razón social</label>
                                        <input x-model="form.razon_social" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="text-xs font-medium text-slate-700">Email</label>
                                        <input x-model="form.email" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="px-6 py-4 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50" @click="$dispatch('close-modal','cliente-form')">Cancelar</button>
                    <button class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90" :disabled="saving" x-text="saving ? 'Guardando…' : 'Guardar'"></button>
                </div>
            </form>
        </x-modal>
    </div>

    <script>
        function clientesPage({ urls }) {
            return {
                urls,
                q: '',
                rows: [],
                loading: false,
                detail: null,
                detailError: '',
                detailTab: 'tickets',
                form: {
                    id: null,
                    telefono: '',
                    nombre: '',
                    email: '',
                    direccion: '',
                    tipo_documento: '',
                    numero_documento: '',
                    razon_social: '',
                },
                formError: '',
                saving: false,
                page: 0,
                perPage: 25,

                init() {
                    window.addEventListener('cliente-new', () => this.resetForm());
                    window.addEventListener('cliente-edit', (ev) => this.fillForm(ev.detail?.cliente || null));
                    this.reload();
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

                get empresaCount() {
                    return (this.rows || []).filter((r) => r.razon_social || String(r.tipo_documento || '').toUpperCase() === 'RUC').length;
                },

                get docCount() {
                    return (this.rows || []).filter((r) => r.tipo_documento && r.numero_documento).length;
                },

                get emailCount() {
                    return (this.rows || []).filter((r) => r.email).length;
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

                applySearch() {
                    this.page = 0;
                },

                async openDetail(id) {
                    this.detailError = '';
                    this.detail = null;
                    this.detailTab = 'tickets';
                    this.$dispatch('open-modal', 'cliente-detalle');
                    try {
                        const res = await window.axios.get(this.urls.show(id), { headers: { 'Accept': 'application/json' } });
                        this.detail = res.data?.data || null;
                        if (res.data?.ok !== true) {
                            this.detailError = res.data?.error || 'No se pudo cargar.';
                        }
                    } catch (e) {
                        this.detailError = e?.response?.data?.error || e?.message || 'Error';
                    }
                },

                resetForm() {
                    this.formError = '';
                    this.form = {
                        id: null,
                        telefono: '',
                        nombre: '',
                        email: '',
                        direccion: '',
                        tipo_documento: '',
                        numero_documento: '',
                        razon_social: '',
                    };
                },

                fillForm(c) {
                    this.formError = '';
                    if (!c) return this.resetForm();
                    this.form = {
                        id: c.id || null,
                        telefono: c.telefono || '',
                        nombre: c.nombre || '',
                        email: c.email || '',
                        direccion: c.direccion || '',
                        tipo_documento: c.tipo_documento || '',
                        numero_documento: c.numero_documento || '',
                        razon_social: c.razon_social || '',
                    };
                },

                async submitForm() {
                    this.formError = '';
                    this.saving = true;
                    try {
                        const payload = {
                            telefono: this.form.telefono,
                            nombre: this.form.nombre || null,
                            direccion: this.form.direccion || null,
                            tipo_documento: this.form.tipo_documento || null,
                            numero_documento: this.form.numero_documento || null,
                            razon_social: this.form.razon_social || null,
                            email: this.form.email || null,
                        };

                        const url = this.form.id ? this.urls.update(this.form.id) : this.urls.create;
                        const res = await window.axios.post(url, payload, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.formError = res.data?.error || 'No se pudo guardar.';
                            return;
                        }
                        this.$dispatch('close-modal', 'cliente-form');
                        await this.reload();
                        const id = this.form.id || res.data?.data?.id;
                        if (id) {
                            await this.openDetail(id);
                        }
                    } catch (e) {
                        this.formError = e?.response?.data?.error || e?.message || 'Error';
                    } finally {
                        this.saving = false;
                    }
                },

                async confirmDestroy(id) {
                    if (!id) return;
                    if (!await window.GCDialog.confirm({ title: 'Eliminar cliente', message: 'Se eliminará la ficha del cliente. Esta acción no se puede deshacer.', confirmText: 'Eliminar', tone: 'danger' })) return;
                    try {
                        const res = await window.axios.post(this.urls.destroy(id), {}, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            window.GCToast?.error('No se pudo eliminar', res.data?.error || 'Error');
                            return;
                        }
                        this.$dispatch('close-modal', 'cliente-detalle');
                        await this.reload();
                        window.GCToast?.success('Cliente eliminado', 'Se eliminó el cliente.');
                    } catch (e) {
                        window.GCToast?.error('Error', e?.response?.data?.error || e?.message || 'Error');
                    }
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

                money(amount, currency) {
                    const n = Number(amount ?? 0);
                    if (Number.isNaN(n)) return String(amount ?? '0');
                    const c = String(currency || 'PEN').toUpperCase();
                    try {
                        return new Intl.NumberFormat('es-PE', { style: 'currency', currency: c, maximumFractionDigits: 2 }).format(n);
                    } catch {
                        return `${n.toFixed(2)} ${c}`;
                    }
                },

                ventaDoc(v) {
                    if (!v) return '—';
                    const tipo = v.tipo_documento || '';
                    const serie = v.serie_documento || '';
                    const num = v.numero_documento || '';
                    const doc = [serie, num].filter(Boolean).join('-');
                    return doc ? `${tipo} ${doc}`.trim() : (tipo || '—');
                },

            }
        }
    </script>
</x-app-layout>
