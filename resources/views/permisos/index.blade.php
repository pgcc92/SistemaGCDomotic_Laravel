<x-app-layout>
    <x-slot name="header">
        <div class="text-lg font-semibold text-slate-900">Permisos</div>
    </x-slot>

    <div
        x-data="permisosPage(@js($matrix))"
        x-init="init()"
        class="space-y-6"
    >
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($error)
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                {{ $error }}
            </div>
        @endif

        <template x-if="!canManage">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                Solo el <span class="font-semibold">administrador principal</span> puede editar permisos. Estás en modo solo lectura.
            </div>
        </template>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3a6 6 0 104.472 10.03l2.249 2.249a1 1 0 001.414-1.414l-2.249-2.249A6 6 0 009 3zm-4 6a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input x-model.debounce.200ms="q" @input="applyFilter()"
                       placeholder="Filtrar módulo…"
                       class="w-full rounded-xl border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-primary focus:ring-primary" />
            </div>
            <div class="flex items-center gap-2">
                <button class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50"
                        :disabled="!canManage"
                        @click="selectAll(true)">
                    Marcar todo
                </button>
                <button class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50"
                        :disabled="!canManage"
                        @click="selectAll(false)">
                    Desmarcar todo
                </button>
                <button class="rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-white hover:bg-primary/90"
                        :disabled="!canManage || saving || changes.length === 0"
                        @click="save()"
                        x-text="!canManage ? 'Solo lectura' : (saving ? 'Guardando…' : (changes.length ? `Guardar (${changes.length})` : 'Guardar'))">
                </button>
            </div>
        </div>

        <div class="gc-card overflow-hidden">
            <div class="overflow-auto">
                <table class="min-w-[900px] w-full text-sm">
                    <thead class="bg-slate-50/60">
                        <tr class="text-left text-xs font-semibold text-slate-600">
                            <th class="sticky left-0 z-10 bg-slate-50/60 border-b border-slate-200/80 px-4 py-3">Módulo / Acción</th>
                            <template x-for="r in roles" :key="r.id">
                                <th class="border-b border-slate-200/80 px-4 py-3 whitespace-nowrap" x-text="r.nombre"></th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="m in filteredModulos" :key="m.id">
                            <template x-for="a in acciones" :key="m.id + '-' + a.id">
                                <tr class="border-b border-slate-100 hover:bg-slate-50">
                                    <td class="sticky left-0 z-10 bg-white px-4 py-3">
                                        <div class="text-xs text-slate-500" x-text="m.nombre"></div>
                                        <div class="font-medium text-slate-900" x-text="accionLabel(a.codigo)"></div>
                                    </td>
                                    <template x-for="r in roles" :key="m.id + '-' + a.id + '-' + r.id">
                                        <td class="px-4 py-3">
                                            <label class="inline-flex items-center gap-2">
                                                <input type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary"
                                                       :checked="getPerm(r.id, m.id, a.id)"
                                                       :disabled="!canManage"
                                                       @change="toggle(r.id, m.id, a.id, $event.target.checked)" />
                                                <span class="text-xs text-slate-500">Permitido</span>
                                            </label>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </template>

                        <tr x-show="filteredModulos.length === 0">
                            <td class="px-4 py-10 text-center text-slate-500" :colspan="1 + roles.length">
                                No hay módulos que coincidan con el filtro.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
            function permisosPage(matrix) {
                return {
                    q: '',
                    roles: [],
                    modulos: [],
                    acciones: [],
                    perms: new Map(),
                    changes: [],
                    saving: false,
                    canManage: true,

                    init() {
                        this.roles = matrix?.roles || [];
                        this.modulos = matrix?.modulos || [];
                        this.acciones = matrix?.acciones || [];
                        this.canManage = matrix?.super_admin?.can_manage !== false;
                        const rp = matrix?.rol_permisos || [];
                        for (const row of rp) {
                            const k = this.key(row.rol_id, row.modulo_id, row.accion_id);
                            this.perms.set(k, !!row.permitido);
                        }
                    },
                    accionLabel(code) {
                        const c = String(code || '').toLowerCase();
                        const map = {
                            ver: 'Ver',
                            ver_general: 'Ver (general)',
                            crear: 'Crear',
                            editar: 'Editar',
                            eliminar: 'Eliminar',
                            exportar: 'Exportar',
                            asignar: 'Asignar',
                            aprobar: 'Aprobar',
                        };
                        return map[c] || code;
                    },

                    get filteredModulos() {
                        const q = (this.q || '').trim().toLowerCase();
                        if (!q) return this.modulos;
                        return this.modulos.filter(m => String(m.nombre || m.codigo || '').toLowerCase().includes(q) || String(m.codigo || '').toLowerCase().includes(q));
                    },

                    key(rolId, moduloId, accionId) {
                        return `${rolId}:${moduloId}:${accionId}`;
                    },

                    getPerm(rolId, moduloId, accionId) {
                        return !!this.perms.get(this.key(rolId, moduloId, accionId));
                    },

                    toggle(rolId, moduloId, accionId, allowed) {
                        if (!this.canManage) return;
                        const k = this.key(rolId, moduloId, accionId);
                        this.perms.set(k, !!allowed);
                        const idx = this.changes.findIndex(c => c.rol_id === rolId && c.modulo_id === moduloId && c.accion_id === accionId);
                        const item = { rol_id: rolId, modulo_id: moduloId, accion_id: accionId, permitido: !!allowed };
                        if (idx >= 0) this.changes[idx] = item;
                        else this.changes.push(item);
                    },

                    applyFilter() {},

                    selectAll(v) {
                        if (!this.canManage) return;
                        for (const m of this.filteredModulos) {
                            for (const a of this.acciones) {
                                for (const r of this.roles) {
                                    this.toggle(r.id, m.id, a.id, !!v);
                                }
                            }
                        }
                    },

                    async save() {
                        if (!this.canManage) return;
                        if (!this.changes.length) return;
                        this.saving = true;
                        try {
                            const res = await window.axios.post('{{ route('permisos.update') }}', { changes: this.changes }, {
                                headers: { 'Accept': 'application/json' },
                            });
                            if (res.data?.ok !== true) {
                                window.GCToast?.error('No se pudo guardar', res.data?.error || 'Error');
                                return;
                            }
                            this.changes = [];
                            window.GCToast?.success('Permisos guardados', 'Se actualizaron los permisos.');
                        } catch (e) {
                            window.GCToast?.error('Error', e?.response?.data?.error || e?.message || 'Error');
                        } finally {
                            this.saving = false;
                        }
                    },
                }
            }
        </script>
    </div>
</x-app-layout>
