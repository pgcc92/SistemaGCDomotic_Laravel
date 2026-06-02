<x-app-layout>
    <x-slot name="header">
        <div class="text-lg font-semibold text-slate-900">Usuarios</div>
    </x-slot>

    <div x-data="usuariosPage({
            urls: {
                data: '{{ route('usuarios.data') }}',
                create: '{{ route('usuarios.store') }}',
                update: (id) => `/usuarios/${id}/editar`,
                destroy: (id) => `/usuarios/${id}/eliminar`,
                permisos: (id) => `/usuarios/${id}/permisos`,
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
                       placeholder="Buscar por documento o nombre…"
                       class="w-full rounded-xl border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-primary focus:ring-primary" />
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-white hover:bg-primary/90"
                        @click="resetForm(); $dispatch('open-modal','usuario-form')">
                    Nuevo usuario
                </button>
            </div>
        </div>

        <x-table>
            <thead class="bg-slate-50/60">
                <tr class="text-left text-xs font-semibold text-slate-600">
                    <th class="px-4 py-3">Documento</th>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Rol</th>
                    <th class="px-4 py-3">Activo</th>
                    <th class="px-4 py-3">Dashboard</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="row in pagedRows" :key="row.id">
                    <tr class="border-b border-slate-100 hover:bg-slate-50 cursor-pointer"
                        @click="fillForm(row); $dispatch('open-modal','usuario-form')">
                        <td class="px-4 py-3 font-medium text-slate-900" x-text="row.numero_documento"></td>
                        <td class="px-4 py-3 text-slate-700" x-text="row.nombre"></td>
                        <td class="px-4 py-3 text-slate-700" x-text="row.email || '—'"></td>
                        <td class="px-4 py-3 text-slate-700" x-text="roleName(row.rol_id)"></td>
                        <td class="px-4 py-3">
                            <x-badge variant="emerald" x-show="row.activo">Sí</x-badge>
                            <x-badge variant="slate" x-show="!row.activo">No</x-badge>
                        </td>
                        <td class="px-4 py-3">
                            <x-badge variant="emerald" x-show="row.dashboard_activo">Sí</x-badge>
                            <x-badge variant="slate" x-show="!row.dashboard_activo">No</x-badge>
                        </td>
                    </tr>
                </template>
                <tr x-show="!loading && filteredRows.length === 0">
                    <td class="px-4 py-10 text-center text-slate-500" colspan="6">No hay usuarios.</td>
                </tr>
            </tbody>
        </x-table>

        <x-pagination page="page" pages="pages"></x-pagination>

        <x-modal name="usuario-form" maxWidth="4xl" focusable>
            <form class="divide-y divide-slate-200" @submit.prevent="submit()">
                <div class="px-6 py-4 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900" x-text="form.id ? 'Editar usuario' : 'Nuevo usuario'"></div>
                        <div class="mt-0.5 text-xs text-slate-500">Solo administradores</div>
                    </div>
                    <x-icon-button @click="$dispatch('close-modal','usuario-form')" aria-label="Cerrar">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </x-icon-button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <template x-if="error">
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="error"></div>
                    </template>
                    <template x-if="createdPassword">
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                            Password generado: <span class="font-mono font-semibold" x-text="createdPassword"></span>
                        </div>
                    </template>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-medium text-slate-700">Número documento</label>
                            <input x-model="form.numero_documento" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" required />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Nombre</label>
                            <input x-model="form.nombre" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" required />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Email</label>
                            <input x-model="form.email" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Teléfono</label>
                            <input x-model="form.telefono" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Rol</label>
                            <select x-model="form.rol_id" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                                <option value="">— Seleccionar —</option>
                                <template x-for="r in roles" :key="r.id">
                                    <option :value="r.id" x-text="r.nombre"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Sucursal ID</label>
                            <input x-model="form.sucursal_id" type="number" min="1" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Técnico ID (opcional)</label>
                            <input x-model="form.tecnico_id" type="number" min="1" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Nuevo password (opcional)</label>
                            <input x-model="form.password" type="password" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                            <div class="mt-1 text-xs text-slate-500">Si lo dejas vacío, no cambia (o se genera en creación).</div>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary" x-model="form.activo" />
                            <span class="text-sm text-slate-700">Activo</span>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary" x-model="form.dashboard_activo" />
                            <span class="text-sm text-slate-700">Dashboard activo</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50"
                                x-show="!!form.id"
                                @click="openPermisos()">
                            Permisos por usuario
                        </button>
                        <button type="button" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700"
                                x-show="!!form.id"
                                @click="destroy()">
                            Eliminar
                        </button>
                    </div>
                </div>
                <div class="px-6 py-4 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50" @click="$dispatch('close-modal','usuario-form')">Cancelar</button>
                    <button class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90" :disabled="saving" x-text="saving ? 'Guardando…' : 'Guardar'"></button>
                </div>
            </form>
        </x-modal>

        <x-modal name="usuario-permisos" maxWidth="4xl">
            <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between bg-white/80 backdrop-blur">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-900">Permisos por usuario</div>
                    <div class="mt-0.5 text-xs text-slate-500" x-text="permUserTitle()"></div>
                </div>
                <x-icon-button @click="$dispatch('close-modal','usuario-permisos')" aria-label="Cerrar">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </x-icon-button>
            </div>
            <div class="p-6 space-y-4">
                <template x-if="permMatrixLoaded && !permCanManage">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        Solo el <span class="font-semibold">administrador principal</span> puede editar permisos. Estás en modo solo lectura.
                    </div>
                </template>

                <template x-if="permError">
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="permError"></div>
                </template>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative w-full sm:max-w-md">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 3a6 6 0 104.472 10.03l2.249 2.249a1 1 0 001.414-1.414l-2.249-2.249A6 6 0 009 3zm-4 6a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input x-model.debounce.200ms="permQ" @input="applyPermFilter()"
                               placeholder="Filtrar módulo…"
                               class="w-full rounded-xl border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-primary focus:ring-primary" />
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50"
                                :disabled="!permCanManage"
                                @click="permSelectAll(true)">Marcar todo</button>
                        <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50"
                                :disabled="!permCanManage"
                                @click="permSelectAll(false)">Desmarcar todo</button>
                        <button type="button" class="rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-white hover:bg-primary/90 disabled:opacity-60"
                                :disabled="!permCanManage || permSaving || permChanges.length===0"
                                @click="permSave()"
                                x-text="!permCanManage ? 'Solo lectura' : (permSaving ? 'Guardando…' : (permChanges.length ? `Guardar (${permChanges.length})` : 'Guardar'))"></button>
                    </div>
                </div>

                <div class="gc-card overflow-hidden">
                    <div class="overflow-auto">
                        <table class="min-w-[900px] w-full text-sm">
                            <thead class="bg-slate-50/60">
                                <tr class="text-left text-xs font-semibold text-slate-600">
                                    <th class="sticky left-0 z-10 bg-slate-50/60 border-b border-slate-200/80 px-4 py-3">Módulo / Acción</th>
                                    <th class="border-b border-slate-200/80 px-4 py-3 whitespace-nowrap">Personalizado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="row in permPermRows" :key="row.key">
                                    <tr :class="row.type==='section' ? 'bg-slate-50/60' : 'border-b border-slate-100 hover:bg-slate-50'"
                                        x-show="row.type==='section' || permIsSectionOpen(row.secKey)">
                                        <template x-if="row.type==='section'">
                                            <td class="sticky left-0 z-10 bg-slate-50/60 px-4 py-3 border-b border-slate-200/80" colspan="2">
                                                <div class="flex items-center justify-between gap-3">
                                                    <button type="button" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700"
                                                            @click="permToggleSection(row.secKey)">
                                                        <svg class="h-4 w-4 text-slate-400 transition" :class="permIsSectionOpen(row.secKey) ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span x-text="row.label"></span>
                                                    </button>
                                                    <div class="flex items-center gap-2">
                                                        <button type="button"
                                                                class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[12px] font-medium text-slate-700 hover:bg-slate-50"
                                                                :disabled="!permCanManage"
                                                                @click="permSelectSection(row.secKey, true)">
                                                            Marcar sección
                                                        </button>
                                                        <button type="button"
                                                                class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[12px] font-medium text-slate-700 hover:bg-slate-50"
                                                                :disabled="!permCanManage"
                                                                @click="permSelectSection(row.secKey, false)">
                                                            Desmarcar sección
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                        </template>

                                        <template x-if="row.type==='module'">
                                            <td class="sticky left-0 z-10 bg-white px-4 py-3" colspan="2">
                                                <div class="flex flex-col gap-3">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div class="min-w-0">
                                                            <div class="text-xs font-semibold text-slate-900" x-text="row.m.nombre"></div>
                                                            <div class="mt-0.5 text-[11px] text-slate-500" x-text="row.m.codigo"></div>
                                                        </div>
                                                        <button type="button"
                                                                class="shrink-0 inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-[12px] font-medium text-slate-700 hover:bg-slate-50"
                                                                @click="permToggleOpen(row.m.id)">
                                                            <span x-text="permIsOpen(row.m.id) ? 'Ocultar' : 'Personalizar'"></span>
                                                            <svg class="h-4 w-4 text-slate-400 transition" :class="permIsOpen(row.m.id) ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                            </svg>
                                                        </button>
                                                    </div>

                                                    <div class="flex flex-wrap items-center gap-3">
                                                        <label class="inline-flex items-center gap-2">
                                                            <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
                                                                   :checked="permGet(row.m.id, permAccionVerId)"
                                                                   :disabled="!permCanManage"
                                                                   @change="if (permAccionVerId) permToggle(row.m.id, permAccionVerId, $event.target.checked)" />
                                                            <span class="text-xs text-slate-600">Ver</span>
                                                        </label>
                                                        <template x-if="permAccionVerGeneralId">
                                                            <label class="inline-flex items-center gap-2">
                                                                <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
                                                                       :checked="permGet(row.m.id, permAccionVerGeneralId)"
                                                                       :disabled="!permCanManage"
                                                                       @change="permToggle(row.m.id, permAccionVerGeneralId, $event.target.checked)" />
                                                                <span class="text-xs text-slate-600">Ver todo</span>
                                                            </label>
                                                        </template>
                                                        <span class="hidden sm:inline text-xs text-slate-400">•</span>
                                                        <button type="button"
                                                                class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[12px] font-medium text-slate-700 hover:bg-slate-50"
                                                                :disabled="!permCanManage"
                                                                @click="permSelectModule(row.m.id, true)">
                                                            Marcar módulo
                                                        </button>
                                                        <button type="button"
                                                                class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[12px] font-medium text-slate-700 hover:bg-slate-50"
                                                                :disabled="!permCanManage"
                                                                @click="permSelectModule(row.m.id, false)">
                                                            Desmarcar módulo
                                                        </button>
                                                    </div>

                                                    <div x-show="permIsOpen(row.m.id)" x-collapse class="pt-1">
                                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                                            <template x-for="a in permAcciones" :key="row.m.id + '-a-' + a.id">
                                                                <label class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/40 px-3 py-2 hover:bg-slate-50">
                                                                    <div class="min-w-0">
                                                                        <div class="text-xs font-semibold text-slate-800" x-text="a.codigo"></div>
                                                                        <div class="mt-0.5 text-[11px] text-slate-500">Acción</div>
                                                                    </div>
                                                                    <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"
                                                                           :checked="permGet(row.m.id,a.id)"
                                                                           :disabled="!permCanManage"
                                                                           @change="permToggle(row.m.id,a.id,$event.target.checked)" />
                                                                </label>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </template>
                                    </tr>
                                </template>

                                <tr x-show="permMatrixLoaded && permPermRows.length===0">
                                    <td class="px-4 py-10 text-center text-slate-500" colspan="2">Sin resultados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </x-modal>
    </div>

    <script>
        function usuariosPage({ urls }) {
            return {
                urls,
                q: '',
                rows: [],
                roles: [],
                loading: false,
                page: 0,
                perPage: 25,
                saving: false,
                error: '',
                createdPassword: '',
                form: { id: null, numero_documento: '', nombre: '', email: '', telefono: '', rol_id: '', sucursal_id: '', tecnico_id: '', activo: true, dashboard_activo: true, password: '' },
                userPerms: null,
                permUser: null,
                permMatrix: null,
                permMatrixLoaded: false,
                permError: '',
                permSaving: false,
                permQ: '',
                permChanges: [],
                permModulos: [],
                permAcciones: [],
                permFilteredModulos: [],
                permUserPermsMap: {},
                permUserPermsOriginalMap: {},
                permOpenModules: {},
                permOpenSections: {},

                async init() { await this.reload(); },

                async reload() {
                    this.loading = true;
                    try {
                        const res = await window.axios.get(this.urls.data, { headers: { 'Accept': 'application/json' } });
                        this.rows = res.data?.data || [];
                        this.roles = res.data?.meta?.roles || [];
                        this.page = 0;
                    } finally {
                        this.loading = false;
                    }
                },

                get filteredRows() {
                    const q = (this.q || '').trim().toLowerCase();
                    if (!q) return this.rows;
                    return this.rows.filter(r =>
                        String(r.numero_documento || '').toLowerCase().includes(q) ||
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
                    this.createdPassword = '';
                    this.form = { id: null, numero_documento: '', nombre: '', email: '', telefono: '', rol_id: '', sucursal_id: '', tecnico_id: '', activo: true, dashboard_activo: true, password: '' };
                },

                fillForm(r) {
                    this.error = '';
                    this.createdPassword = '';
                    this.form = {
                        id: r.id,
                        numero_documento: r.numero_documento || '',
                        nombre: r.nombre || '',
                        email: r.email || '',
                        telefono: r.telefono || '',
                        rol_id: r.rol_id || '',
                        sucursal_id: r.sucursal_id || '',
                        tecnico_id: r.tecnico_id || '',
                        activo: !!r.activo,
                        dashboard_activo: !!r.dashboard_activo,
                        password: '',
                    };
                },

                roleName(id) {
                    const rid = Number(id || 0);
                    if (!rid) return '—';
                    const r = (this.roles || []).find(x => Number(x.id) === rid);
                    return r?.nombre || `#${rid}`;
                },

                permUserTitle() {
                    const u = this.permUser;
                    if (!u) return '—';
                    const rol = this.roleName(u.rol_id);
                    return `${u.nombre || 'Usuario'} · ${u.numero_documento || ''} · ${rol}`;
                },

                async openPerms(row) {
                    this.permUser = row;
                    this.permError = '';
                    this.permChanges = [];
                    this.permQ = '';
                    this.permMatrixLoaded = false;
                    this.permCanManage = true;
                    this.permUserPermsMap = {};
                    this.permUserPermsOriginalMap = {};
                    this.permOpenModules = {};
                    this.permOpenSections = { operacion: true, inventario: true, administracion: true, otros: true };
                    this.permFilteredModulos = [];
                    this.$dispatch('open-modal','usuario-permisos');
                    await this.loadPermMatrix();
                    await this.loadUserPerms(row.id);
                    this.applyPermFilter();
                },

                async loadPermMatrix() {
                    if (this.permMatrixLoaded) return;
                    try {
                        const res = await window.axios.get('{{ route('permisos.matrix-data') }}', { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) throw new Error(res.data?.error || 'No se pudo cargar matriz');
                        this.permMatrix = res.data?.data || null;
                        const d = this.permMatrix || {};
                        this.permModulos = d?.modulos || d?.data?.modulos || [];
                        this.permAcciones = d?.acciones || d?.data?.acciones || [];
                        this.permCanManage = d?.super_admin?.can_manage !== false;
                        // Si viene envuelto en {kpis/series}, no aplica: usamos fallback
                        if (!Array.isArray(this.permModulos) && Array.isArray(d?.data?.modulos)) this.permModulos = d.data.modulos;
                        if (!Array.isArray(this.permAcciones) && Array.isArray(d?.data?.acciones)) this.permAcciones = d.data.acciones;
                        this.permMatrixLoaded = true;
                    } catch (e) {
                        this.permError = e?.response?.data?.error || e?.message || 'No se pudo cargar permisos.';
                        window.GCToast?.error?.('Permisos', this.permError);
                    }
                },

                async loadUserPerms(userId) {
                    try {
                        const res = await window.axios.get(this.urls.permisos(userId), { headers: { 'Accept': 'application/json' } });
                        const rows = res.data?.data || [];
                        const map = {};
                        for (const r of rows) {
                            const key = `${r.modulo_id}:${r.accion_id}`;
                            map[key] = !!r.permitido;
                        }
                        this.permUserPermsMap = map;
                        this.permUserPermsOriginalMap = { ...map };
                    } catch (e) {
                        this.permError = e?.response?.data?.error || e?.message || 'No se pudo cargar permisos del usuario.';
                        window.GCToast?.error?.('Permisos', this.permError);
                    }
                },

                applyPermFilter() {
                    const q = (this.permQ || '').trim().toLowerCase();
                    const mods = Array.isArray(this.permModulos) ? this.permModulos : [];
                    this.permFilteredModulos = !q ? mods : mods.filter(m => String(m.nombre||'').toLowerCase().includes(q) || String(m.codigo||'').toLowerCase().includes(q));
                },

                get permAccionVerId() {
                    const a = (this.permAcciones || []).find(x => String(x.codigo).toLowerCase() === 'ver');
                    return a?.id ? Number(a.id) : 0;
                },

                get permAccionVerGeneralId() {
                    const a = (this.permAcciones || []).find(x => String(x.codigo).toLowerCase() === 'ver_general');
                    return a?.id ? Number(a.id) : 0;
                },

                permIsSectionOpen(secKey) {
                    return this.permOpenSections?.[String(secKey)] !== false;
                },

                permToggleSection(secKey) {
                    const k = String(secKey);
                    this.permOpenSections[k] = !this.permIsSectionOpen(k);
                },

                permIsOpen(moduloId) {
                    return !!this.permOpenModules?.[String(moduloId)];
                },

                permToggleOpen(moduloId) {
                    const k = String(moduloId);
                    this.permOpenModules[k] = !this.permOpenModules?.[k];
                },

                permSelectModule(moduloId, val) {
                    const v = !!val;
                    for (const a of (this.permAcciones || [])) {
                        this.permToggle(moduloId, a.id, v);
                    }
                },

                get permPermRows() {
                    const out = [];
                    for (const sec of (this.permSectionsFiltered || [])) {
                        out.push({ type: 'section', key: `sec:${sec.key}`, secKey: sec.key, label: sec.label });
                        for (const m of (sec.modulos || [])) {
                            out.push({ type: 'module', key: `mod:${sec.key}:${m.id}`, secKey: sec.key, m });
                        }
                    }
                    return out;
                },

                get permSectionsFiltered() {
                    const list = Array.isArray(this.permFilteredModulos) ? this.permFilteredModulos : [];
                    const groups = {
                        operacion: ['clientes','tickets','agenda','ventas','comisiones','reportes'],
                        inventario: ['productos','stock','dispositivos','soporte_videos'],
                        administracion: ['usuarios','roles','permisos','sucursales','auditoria','configuracion'],
                    };
                    const order = [
                        { key: 'operacion', label: 'Operación' },
                        { key: 'inventario', label: 'Inventario' },
                        { key: 'administracion', label: 'Administración' },
                        { key: 'otros', label: 'Otros' },
                    ];

                    const byCode = (arr, codes) => arr.filter(m => codes.includes(String(m.codigo || '').toLowerCase()));
                    const usedIds = new Set();

                    const out = [];
                    for (const o of order) {
                        let mods = [];
                        if (o.key === 'otros') {
                            mods = list.filter(m => !usedIds.has(Number(m.id)));
                        } else {
                            mods = byCode(list, groups[o.key] || []);
                        }
                        if (!mods.length) continue;
                        for (const m of mods) usedIds.add(Number(m.id));
                        out.push({ key: o.key, label: o.label, modulos: mods });
                    }
                    return out;
                },

                permGet(moduloId, accionId) {
                    const key = `${Number(moduloId)}:${Number(accionId)}`;
                    return !!this.permUserPermsMap[key];
                },

                permToggle(moduloId, accionId, permitido) {
                    if (!this.permCanManage) return;
                    const key = `${Number(moduloId)}:${Number(accionId)}`;
                    const v = !!permitido;
                    this.permUserPermsMap[key] = v;

                    const orig = !!this.permUserPermsOriginalMap[key];
                    const idx = this.permChanges.findIndex(c => Number(c.modulo_id)===Number(moduloId) && Number(c.accion_id)===Number(accionId));

                    if (v === orig) {
                        if (idx >= 0) this.permChanges.splice(idx, 1);
                        return;
                    }

                    const change = { modulo_id: Number(moduloId), accion_id: Number(accionId), permitido: v };
                    if (idx >= 0) this.permChanges[idx] = change;
                    else this.permChanges.push(change);
                },

                permSelectAll(val) {
                    if (!this.permCanManage) return;
                    const v = !!val;
                    for (const m of (this.permFilteredModulos || [])) {
                        for (const a of (this.permAcciones || [])) {
                            this.permToggle(m.id, a.id, v);
                        }
                    }
                },

                permSelectSection(sectionKey, val) {
                    if (!this.permCanManage) return;
                    const v = !!val;
                    const sec = (this.permSectionsFiltered || []).find(s => String(s.key) === String(sectionKey));
                    const mods = sec?.modulos || [];
                    for (const m of mods) {
                        for (const a of (this.permAcciones || [])) {
                            this.permToggle(m.id, a.id, v);
                        }
                    }
                },

                async permSave() {
                    if (!this.permCanManage) return;
                    if (!this.permUser?.id || !this.permChanges.length) return;
                    this.permSaving = true;
                    try {
                        const res = await window.axios.post(this.urls.permisos(this.permUser.id), { changes: this.permChanges }, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) throw new Error(res.data?.error || 'No se pudo guardar.');
                        this.permChanges = [];
                        this.permUserPermsOriginalMap = { ...(this.permUserPermsMap || {}) };
                        window.GCToast?.success?.('Permisos', 'Se guardaron los permisos del usuario.');
                    } catch (e) {
                        window.GCToast?.error?.('Permisos', e?.response?.data?.error || e?.message || 'Error');
                    } finally {
                        this.permSaving = false;
                    }
                },

                async submit() {
                    this.error = '';
                    this.createdPassword = '';
                    this.saving = true;
                    try {
                        const payload = {
                            numero_documento: this.form.numero_documento,
                            nombre: this.form.nombre,
                            email: this.form.email || null,
                            telefono: this.form.telefono || null,
                            rol_id: this.form.rol_id ? Number(this.form.rol_id) : null,
                            sucursal_id: this.form.sucursal_id ? Number(this.form.sucursal_id) : null,
                            tecnico_id: this.form.tecnico_id ? Number(this.form.tecnico_id) : null,
                            activo: !!this.form.activo,
                            dashboard_activo: !!this.form.dashboard_activo,
                            password: this.form.password || null,
                        };
                        const url = this.form.id ? this.urls.update(this.form.id) : this.urls.create;
                        const res = await window.axios.post(url, payload, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.error = res.data?.error || 'No se pudo guardar.';
                            return;
                        }
                        if (!this.form.id && res.data?.data?.password_plain) {
                            this.createdPassword = res.data.data.password_plain;
                        }
                        this.$dispatch('close-modal', 'usuario-form');
                        await this.reload();
                    } catch (e) {
                        this.error = e?.response?.data?.error || e?.message || 'Error';
                    } finally {
                        this.saving = false;
                    }
                },

                async destroy() {
                    if (!this.form.id) return;
                    if (!await window.GCDialog.confirm({ title: 'Eliminar usuario', message: 'Esta acción no se puede deshacer.', confirmText: 'Eliminar', tone: 'danger' })) return;
                    this.error = '';
                    try {
                        const res = await window.axios.post(this.urls.destroy(this.form.id), {}, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.error = res.data?.error || 'No se pudo eliminar.';
                            return;
                        }
                        this.$dispatch('close-modal', 'usuario-form');
                        await this.reload();
                    } catch (e) {
                        this.error = e?.response?.data?.error || e?.message || 'Error';
                    }
                },

                async openPermisos() {
                    if (!this.form.id) return;
                    const row = (this.rows || []).find(r => Number(r.id) === Number(this.form.id)) || { ...this.form };
                    await this.openPerms(row);
                },

                json(v) {
                    try { return JSON.stringify(v, null, 2); } catch { return String(v); }
                },
            }
        }
    </script>
</x-app-layout>
