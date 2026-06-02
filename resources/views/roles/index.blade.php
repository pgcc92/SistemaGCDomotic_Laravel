<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <div class="text-lg font-semibold text-slate-900">Roles</div>
            <div class="text-sm text-slate-500">Crea y administra perfiles de acceso.</div>
        </div>
    </x-slot>

    <div x-data="rolesPage({
            urls: {
                data: '{{ route('roles.data') }}',
                create: '{{ route('roles.store') }}',
                update: (id) => `/roles/${id}/editar`,
                destroy: (id) => `/roles/${id}/eliminar`,
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
                        @click="resetForm(); $dispatch('open-modal','rol-form')">
                    Nuevo rol
                </button>
            </div>
        </div>

        <div class="gc-card p-5">
            <x-table class="border-0 shadow-none">
                <thead class="bg-slate-50/60">
                    <tr class="text-left text-xs font-semibold text-slate-600">
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Protegido</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in pagedRows" :key="row.id">
                        <tr class="border-b border-slate-100 hover:bg-slate-50 cursor-pointer"
                            @click="fillForm(row); $dispatch('open-modal','rol-form')">
                            <td class="px-4 py-3 font-medium text-slate-900" x-text="row.codigo"></td>
                            <td class="px-4 py-3 text-slate-700" x-text="row.nombre"></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                      :class="row.protegido ? 'bg-slate-100 text-slate-700 ring-slate-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200'"
                                      x-text="row.protegido ? 'Sí' : 'No'"></span>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && filteredRows.length === 0">
                        <td class="px-4 py-10 text-center text-slate-500" colspan="3">No hay roles.</td>
                    </tr>
                </tbody>
            </x-table>
        </div>

        <x-pagination page="page" pages="pages"></x-pagination>

        <x-modal name="rol-form" maxWidth="2xl" focusable>
            <form class="divide-y divide-slate-200" @submit.prevent="submit()">
                <div class="px-6 py-4 flex items-start justify-between gap-3 bg-white/80 backdrop-blur">
                    <div>
                        <div class="text-sm font-semibold text-slate-900" x-text="form.id ? 'Editar rol' : 'Nuevo rol'"></div>
                        <div class="mt-0.5 text-xs text-slate-500">Administra perfiles de acceso.</div>
                    </div>
                    <x-icon-button @click="$dispatch('close-modal','rol-form')" aria-label="Cerrar">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </x-icon-button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <template x-if="error">
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="error"></div>
                    </template>

                    <div>
                        <label class="text-xs font-medium text-slate-700">Código</label>
                        <input x-model="form.codigo" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" required />
                        <div class="mt-1 text-xs text-slate-500">Ej: <span class="font-mono">vendedor</span>, <span class="font-mono">tecnico</span>, <span class="font-mono">instalador</span>.</div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-700">Nombre</label>
                        <input x-model="form.nombre" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" required />
                    </div>
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">Rol protegido</div>
                            <div class="mt-0.5 text-xs text-slate-500">Un rol protegido no se puede eliminar/editar desde UI.</div>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" x-model="form.protegido" class="rounded border-slate-300 text-primary focus:ring-primary" />
                            Protegido
                        </label>
                    </div>
                </div>
                <div class="px-6 py-4 flex items-center justify-between gap-2 bg-white/80 backdrop-blur">
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50"
                            @click="$dispatch('close-modal','rol-form')">Cancelar</button>
                    <div class="flex items-center gap-2">
                        <button type="button" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100 disabled:opacity-60"
                                :disabled="!form.id || form.protegido || saving"
                                @click="destroy()">
                            Eliminar
                        </button>
                        <button class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90 disabled:opacity-60"
                                :disabled="saving"
                                x-text="saving ? 'Guardando…' : 'Guardar'"></button>
                    </div>
                </div>
            </form>
        </x-modal>
    </div>

    <script>
        function rolesPage({ urls }) {
            return {
                urls,
                q: '',
                rows: [],
                loading: false,
                page: 0,
                perPage: 25,
                saving: false,
                error: '',
                form: { id: null, codigo: '', nombre: '', protegido: false },

                async init() {
                    await this.reload();
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
                    return this.rows.filter(r =>
                        String(r.codigo || '').toLowerCase().includes(q) ||
                        String(r.nombre || '').toLowerCase().includes(q)
                    );
                },

                get pages() {
                    return Math.max(1, Math.ceil(this.filteredRows.length / this.perPage));
                },

                get pagedRows() {
                    const start = this.page * this.perPage;
                    return this.filteredRows.slice(start, start + this.perPage);
                },

                resetForm() {
                    this.error = '';
                    this.form = { id: null, codigo: '', nombre: '', protegido: false };
                },

                fillForm(row) {
                    this.error = '';
                    this.form = {
                        id: row.id,
                        codigo: row.codigo || '',
                        nombre: row.nombre || '',
                        protegido: !!row.protegido,
                    };
                },

                async submit() {
                    this.error = '';
                    this.saving = true;
                    try {
                        const payload = {
                            codigo: String(this.form.codigo || '').trim(),
                            nombre: String(this.form.nombre || '').trim(),
                            protegido: !!this.form.protegido,
                        };
                        const url = this.form.id ? this.urls.update(this.form.id) : this.urls.create;
                        const res = await window.axios.post(url, payload, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.error = res.data?.error || 'No se pudo guardar.';
                            window.GCToast?.error?.(this.error);
                            return;
                        }
                        await this.reload();
                        window.GCToast?.success?.('Rol guardado');
                        this.$dispatch('close-modal', 'rol-form');
                    } catch (e) {
                        this.error = e?.response?.data?.error || e?.message || 'Error';
                        window.GCToast?.error?.(this.error);
                    } finally {
                        this.saving = false;
                    }
                },

                async destroy() {
                    if (!this.form.id) return;
                    this.error = '';
                    this.saving = true;
                    try {
                        const res = await window.axios.post(this.urls.destroy(this.form.id), {}, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.error = res.data?.error || 'No se pudo eliminar.';
                            window.GCToast?.error?.(this.error);
                            return;
                        }
                        await this.reload();
                        window.GCToast?.success?.('Rol eliminado');
                        this.$dispatch('close-modal', 'rol-form');
                    } catch (e) {
                        this.error = e?.response?.data?.error || e?.message || 'Error';
                        window.GCToast?.error?.(this.error);
                    } finally {
                        this.saving = false;
                    }
                },
            }
        }
    </script>
</x-app-layout>

