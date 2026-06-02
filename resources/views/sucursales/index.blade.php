<x-app-layout>
    <x-slot name="header">
        <div class="text-lg font-semibold text-slate-900">Sucursales</div>
    </x-slot>

    <div x-data="sucursalesPage({
            urls: {
                data: '{{ route('sucursales.data') }}',
                create: '{{ route('sucursales.store') }}',
                update: (id) => `/sucursales/${id}/editar`,
                destroy: (id) => `/sucursales/${id}/eliminar`,
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
                       placeholder="Buscar por código o nombre…"
                       class="w-full rounded-xl border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-primary focus:ring-primary" />
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-white hover:bg-primary/90"
                        @click="resetForm(); $dispatch('open-modal','sucursal-form')">
                    Nueva sucursal
                </button>
            </div>
        </div>

        <x-table>
            <thead class="bg-slate-50/60">
                <tr class="text-left text-xs font-semibold text-slate-600">
                    <th class="px-4 py-3">Código</th>
                    <th class="px-4 py-3">Sucursal</th>
                    <th class="px-4 py-3">Ciudad</th>
                    <th class="px-4 py-3">Teléfono</th>
                    <th class="px-4 py-3">Activo</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="row in pagedRows" :key="row.id">
                    <tr class="border-b border-slate-100 hover:bg-slate-50 cursor-pointer"
                        @click="fillForm(row); $dispatch('open-modal','sucursal-form')">
                        <td class="px-4 py-3 font-medium text-slate-900" x-text="row.codigo"></td>
                        <td class="px-4 py-3 text-slate-700" x-text="row.nombre"></td>
                        <td class="px-4 py-3 text-slate-700" x-text="row.ciudad || '—'"></td>
                        <td class="px-4 py-3 text-slate-700" x-text="row.telefono || '—'"></td>
                        <td class="px-4 py-3">
                            <x-badge variant="emerald" x-show="row.activo">Sí</x-badge>
                            <x-badge variant="slate" x-show="!row.activo">No</x-badge>
                        </td>
                    </tr>
                </template>
                <tr x-show="!loading && filteredRows.length === 0">
                    <td class="px-4 py-10 text-center text-slate-500" colspan="5">No hay sucursales.</td>
                </tr>
            </tbody>
        </x-table>

        <x-pagination page="page" pages="pages"></x-pagination>

        <x-modal name="sucursal-form" maxWidth="3xl" focusable>
            <form class="divide-y divide-slate-200" @submit.prevent="submit()">
                <div class="px-6 py-4 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900" x-text="form.id ? 'Editar sucursal' : 'Nueva sucursal'"></div>
                        <div class="mt-0.5 text-xs text-slate-500">Solo administradores</div>
                    </div>
                    <x-icon-button @click="$dispatch('close-modal','sucursal-form')" aria-label="Cerrar">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </x-icon-button>
                </div>
                <div class="px-6 py-5">
                    <template x-if="error">
                        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="error"></div>
                    </template>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-medium text-slate-700">Código</label>
                            <input x-model="form.codigo" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" required />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Nombre</label>
                            <input x-model="form.nombre" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" required />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Dirección</label>
                            <textarea x-model="form.direccion" rows="2" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"></textarea>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Teléfono</label>
                            <input x-model="form.telefono" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Ciudad</label>
                            <input x-model="form.ciudad" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Encargado ID (opcional)</label>
                            <input x-model="form.encargado_id" type="number" min="1" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div class="flex items-center gap-2 pt-6">
                            <input type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary" x-model="form.activo" />
                            <span class="text-sm text-slate-700">Activo</span>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 flex items-center justify-between gap-2">
                    <button type="button" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700"
                            x-show="!!form.id"
                            @click="destroy()">
                        Eliminar
                    </button>
                    <div class="flex items-center justify-end gap-2 flex-1">
                        <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50" @click="$dispatch('close-modal','sucursal-form')">Cancelar</button>
                        <button class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90" :disabled="saving" x-text="saving ? 'Guardando…' : 'Guardar'"></button>
                    </div>
                </div>
            </form>
        </x-modal>
    </div>

    <script>
        function sucursalesPage({ urls }) {
            return {
                urls,
                q: '',
                rows: [],
                loading: false,
                page: 0,
                perPage: 25,
                saving: false,
                error: '',
                form: { id: null, codigo: '', nombre: '', direccion: '', telefono: '', ciudad: '', encargado_id: '', activo: true },

                async init() { await this.reload(); },

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
                    return this.rows.filter(r =>
                        String(r.codigo || '').toLowerCase().includes(q) ||
                        String(r.nombre || '').toLowerCase().includes(q)
                    );
                },

                get pages() { return Math.max(1, Math.ceil(this.filteredRows.length / this.perPage)); },
                get pagedRows() {
                    const start = this.page * this.perPage;
                    return this.filteredRows.slice(start, start + this.perPage);
                },

                resetForm() {
                    this.error = '';
                    this.form = { id: null, codigo: '', nombre: '', direccion: '', telefono: '', ciudad: '', encargado_id: '', activo: true };
                },

                fillForm(r) {
                    this.error = '';
                    this.form = {
                        id: r.id,
                        codigo: r.codigo || '',
                        nombre: r.nombre || '',
                        direccion: r.direccion || '',
                        telefono: r.telefono || '',
                        ciudad: r.ciudad || '',
                        encargado_id: r.encargado_id || '',
                        activo: !!r.activo,
                    };
                },

                async submit() {
                    this.error = '';
                    this.saving = true;
                    try {
                        const payload = {
                            codigo: this.form.codigo,
                            nombre: this.form.nombre,
                            direccion: this.form.direccion || null,
                            telefono: this.form.telefono || null,
                            ciudad: this.form.ciudad || null,
                            encargado_id: this.form.encargado_id ? Number(this.form.encargado_id) : null,
                            activo: !!this.form.activo,
                        };
                        const url = this.form.id ? this.urls.update(this.form.id) : this.urls.create;
                        const res = await window.axios.post(url, payload, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.error = res.data?.error || 'No se pudo guardar.';
                            return;
                        }
                        this.$dispatch('close-modal', 'sucursal-form');
                        await this.reload();
                    } catch (e) {
                        this.error = e?.response?.data?.error || e?.message || 'Error';
                    } finally {
                        this.saving = false;
                    }
                },

                async destroy() {
                    if (!this.form.id) return;
                    if (!await window.GCDialog.confirm({ title: 'Eliminar sucursal', message: 'Esta acción no se puede deshacer.', confirmText: 'Eliminar', tone: 'danger' })) return;
                    this.error = '';
                    try {
                        const res = await window.axios.post(this.urls.destroy(this.form.id), {}, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.error = res.data?.error || 'No se pudo eliminar.';
                            return;
                        }
                        this.$dispatch('close-modal', 'sucursal-form');
                        await this.reload();
                    } catch (e) {
                        this.error = e?.response?.data?.error || e?.message || 'Error';
                    }
                },
            }
        }
    </script>
</x-app-layout>
