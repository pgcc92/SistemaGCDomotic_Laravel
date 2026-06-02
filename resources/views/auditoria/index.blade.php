<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <div class="text-lg font-semibold text-slate-900">Auditoría</div>
            <div class="text-sm text-slate-500">Registro de acciones del sistema (login, cambios, permisos, etc.).</div>
        </div>
    </x-slot>

    <div
        x-data="auditoriaPage({ urls: { data: '{{ route('auditoria.data') }}' } })"
        x-init="init()"
        class="space-y-6"
    >
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="gc-card p-5 bg-gradient-to-br from-slate-50 to-white">
                <div class="text-xs font-semibold text-slate-600/80">Total (visible)</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.total"></div>
                <div class="mt-1 text-xs text-slate-500">Últimos eventos cargados.</div>
            </div>
            <div class="gc-card p-5 bg-gradient-to-br from-emerald-50 to-white">
                <div class="text-xs font-semibold text-emerald-700/80">Hoy</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.hoy"></div>
                <div class="mt-1 text-xs text-slate-500">Eventos con fecha de hoy.</div>
            </div>
            <div class="gc-card p-5 bg-gradient-to-br from-sky-50 to-white">
                <div class="text-xs font-semibold text-sky-700/80">Logins</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.logins"></div>
                <div class="mt-1 text-xs text-slate-500">Acciones de inicio de sesión.</div>
            </div>
            <div class="gc-card p-5 bg-gradient-to-br from-amber-50 to-white">
                <div class="text-xs font-semibold text-amber-700/80">Cambios críticos</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.criticos"></div>
                <div class="mt-1 text-xs text-slate-500">Permisos, usuarios, configuración.</div>
            </div>
        </div>

        <div class="gc-card p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                    <div class="relative w-full sm:w-[420px]">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 3a6 6 0 104.472 10.03l2.249 2.249a1 1 0 001.414-1.414l-2.249-2.249A6 6 0 009 3zm-4 6a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input x-model.debounce.250ms="q"
                               placeholder="Buscar por usuario, acción, entidad, IP…"
                               class="w-full rounded-xl border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-primary focus:ring-primary" />
                    </div>

                    <select x-model="fAccion"
                            class="w-full rounded-xl border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-primary focus:ring-primary sm:w-56">
                        <option value="">Acción (todas)</option>
                        <template x-for="a in accionesDisponibles" :key="a">
                            <option :value="a" x-text="a"></option>
                        </template>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button type="button"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50"
                            @click="reload()"
                            :disabled="loading">
                        Recargar
                    </button>
                </div>
            </div>

            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="overflow-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/60">
                            <tr class="text-left text-xs font-semibold text-slate-600">
                                <th class="px-4 py-3 whitespace-nowrap">Fecha</th>
                                <th class="px-4 py-3 whitespace-nowrap">Usuario</th>
                                <th class="px-4 py-3 whitespace-nowrap">Acción</th>
                                <th class="px-4 py-3 whitespace-nowrap">Entidad</th>
                                <th class="px-4 py-3 whitespace-nowrap">Ref.</th>
                                <th class="px-4 py-3 whitespace-nowrap">IP</th>
                                <th class="px-4 py-3 whitespace-nowrap text-right">Detalle</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="row in pagedRows" :key="row.id">
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-700" x-text="fmtDate(row.created_at)"></td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-slate-900" x-text="row.usuario_nombre || '—'"></div>
                                        <div class="text-xs text-slate-500" x-text="row.usuario_documento || ''"></div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                              :class="accionClass(row.accion)"
                                              x-text="row.accion || '—'"></span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                              :class="entidadClass(row.entidad)"
                                              x-text="row.entidad || '—'"></span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200 font-mono"
                                              x-text="row.entidad_id || '—'"></span>
                                        <div class="mt-1 text-[11px] text-slate-500" x-text="refHint(row.entidad, row.entidad_id)"></div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-700" x-text="row.ip || '—'"></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        <button type="button"
                                                class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                                @click="openRow(row)">
                                            Ver
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            <tr x-show="!loading && filteredRows.length === 0">
                                <td class="px-4 py-10 text-center text-sm text-slate-500" colspan="7">Sin registros.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3">
                <div class="text-xs text-slate-500" x-text="loading ? 'Cargando…' : `${filteredRows.length} registro(s)`"></div>
                <div class="flex items-center gap-2">
                    <button type="button"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50 disabled:opacity-50"
                            :disabled="page <= 0"
                            @click="page = Math.max(0, page - 1)">
                        Anterior
                    </button>
                    <div class="text-sm text-slate-600" x-text="`Página ${page + 1} de ${pages}`"></div>
                    <button type="button"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50 disabled:opacity-50"
                            :disabled="page >= pages - 1"
                            @click="page = Math.min(pages - 1, page + 1)">
                        Siguiente
                    </button>
                </div>
            </div>
        </div>

        <x-modal name="auditoria-detalle" maxWidth="4xl">
            <div class="border-b border-slate-200 px-6 py-4 bg-white/80 backdrop-blur">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="text-sm font-semibold text-slate-900">Detalle de auditoría</div>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                  :class="accionClass(sel?.accion)"
                                  x-text="sel?.accion || '—'"></span>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                  :class="entidadClass(sel?.entidad)"
                                  x-text="sel?.entidad || '—'"></span>
                            <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200 font-mono"
                                  x-text="sel?.entidad_id || '—'"></span>
                        </div>
                        <div class="mt-0.5 text-xs text-slate-500" x-text="fmtDate(sel?.created_at)"></div>
                    </div>
                    <x-icon-button @click="$dispatch('close-modal','auditoria-detalle')" aria-label="Cerrar">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </x-icon-button>
                </div>
            </div>

            <div class="px-6 py-5 space-y-4">
                <div class="grid gap-4 lg:grid-cols-12">
                    <div class="lg:col-span-5 space-y-3">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold text-slate-900">Actor</div>
                                <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-200"
                                      x-text="sel?.usuario_id ? `#${sel.usuario_id}` : ''"></span>
                            </div>
                            <div class="mt-4 flex items-center gap-3">
                                <div class="h-10 w-10 rounded-2xl bg-slate-100 ring-1 ring-inset ring-slate-200 flex items-center justify-center text-sm font-semibold text-slate-700"
                                     x-text="(sel?.usuario_nombre || '—').slice(0,1).toUpperCase()"></div>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-900" x-text="sel?.usuario_nombre || '—'"></div>
                                    <div class="truncate text-xs text-slate-500" x-text="sel?.usuario_email || sel?.usuario_documento || ''"></div>
                                </div>
                            </div>

                            <div class="mt-4 space-y-3 text-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="text-slate-500">Usuario</div>
                                    <div class="font-medium text-slate-900 text-right" x-text="sel?.usuario_nombre || '—'"></div>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <div class="text-slate-500">Documento</div>
                                    <div class="font-medium text-slate-900 text-right" x-text="sel?.usuario_documento || '—'"></div>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <div class="text-slate-500">Email</div>
                                    <div class="font-medium text-slate-900 text-right" x-text="sel?.usuario_email || '—'"></div>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <div class="text-slate-500">IP</div>
                                    <div class="font-medium text-slate-900 text-right" x-text="sel?.ip || '—'"></div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="text-sm font-semibold text-slate-900">Referencia</div>
                            <div class="mt-4 space-y-3 text-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="text-slate-500">Entidad</div>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                          :class="entidadClass(sel?.entidad)"
                                          x-text="sel?.entidad || '—'"></span>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <div class="text-slate-500">Ref.</div>
                                    <span class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200 font-mono"
                                          x-text="sel?.entidad_id || '—'"></span>
                                </div>
                                <div class="text-xs text-slate-500 leading-relaxed"
                                     x-text="refHint(sel?.entidad, sel?.entidad_id)"></div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-7">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold text-slate-900">Payload</div>
                                <span class="text-xs text-slate-500" x-text="payloadSize(sel?.payload)"></span>
                            </div>
                            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 overflow-auto max-h-[52vh]">
                                <pre class="text-xs leading-relaxed text-slate-800" x-text="prettyPayload(sel?.payload)"></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-modal>
    </div>

    <script>
        function auditoriaPage({ urls }) {
            return {
                urls,
                rows: [],
                q: '',
                fAccion: '',
                loading: false,
                page: 0,
                perPage: 25,
                sel: null,

                init() {
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

                get accionesDisponibles() {
                    const set = new Set((this.rows || []).map(r => String(r?.accion || '').trim()).filter(Boolean));
                    return Array.from(set).sort((a, b) => a.localeCompare(b));
                },

                get filteredRows() {
                    const q = (this.q || '').trim().toLowerCase();
                    const a = (this.fAccion || '').trim().toLowerCase();
                    return (this.rows || []).filter(r => {
                        if (a && String(r?.accion || '').toLowerCase() !== a) return false;
                        if (!q) return true;
                        const blob = [
                            r?.usuario_nombre,
                            r?.usuario_documento,
                            r?.accion,
                            r?.entidad,
                            r?.entidad_id,
                            r?.ip,
                        ].map(x => String(x || '')).join(' ').toLowerCase();
                        return blob.includes(q);
                    });
                },

                get pages() {
                    return Math.max(1, Math.ceil(this.filteredRows.length / this.perPage));
                },

                get pagedRows() {
                    const start = this.page * this.perPage;
                    return this.filteredRows.slice(start, start + this.perPage);
                },

                get kpis() {
                    const rows = this.filteredRows || [];
                    const today = this.todayStr();
                    const hoy = rows.filter(r => String(r?.created_at || '').startsWith(today)).length;
                    const logins = rows.filter(r => String(r?.accion || '') === 'login' || String(r?.accion || '') === 'login_2fa').length;
                    const crit = new Set(['permisos_updated','usuario_permisos_updated','password_changed','twofa_enabled','twofa_disabled','config_updated','roles_updated']);
                    const criticos = rows.filter(r => crit.has(String(r?.accion || ''))).length;
                    return { total: rows.length, hoy, logins, criticos };
                },

                todayStr() {
                    const d = new Date();
                    const yyyy = d.getFullYear();
                    const mm = String(d.getMonth() + 1).padStart(2, '0');
                    const dd = String(d.getDate()).padStart(2, '0');
                    return `${yyyy}-${mm}-${dd}`;
                },

                openRow(row) {
                    this.sel = row;
                    this.$dispatch('open-modal', 'auditoria-detalle');
                },

                fmtDate(v) {
                    if (!v) return '—';
                    const s = String(v).replace(' ', 'T');
                    const d = new Date(s);
                    if (Number.isNaN(d.getTime())) return String(v);
                    const dd = String(d.getDate()).padStart(2, '0');
                    const mm = String(d.getMonth() + 1).padStart(2, '0');
                    const yyyy = d.getFullYear();
                    const hh = String(d.getHours()).padStart(2, '0');
                    const mi = String(d.getMinutes()).padStart(2, '0');
                    return `${dd}/${mm}/${yyyy} ${hh}:${mi}`;
                },

                accionClass(accion) {
                    const a = String(accion || '').toLowerCase();
                    if (a.startsWith('login')) return 'bg-sky-50 text-sky-700 ring-sky-200';
                    if (a === 'logout') return 'bg-slate-100 text-slate-700 ring-slate-200';
                    if (a.includes('permis')) return 'bg-amber-50 text-amber-700 ring-amber-200';
                    if (a.includes('password') || a.includes('twofa')) return 'bg-rose-50 text-rose-700 ring-rose-200';
                    if (a.includes('created') || a.includes('crear')) return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
                    if (a.includes('deleted') || a.includes('eliminar')) return 'bg-rose-50 text-rose-700 ring-rose-200';
                    return 'bg-slate-100 text-slate-700 ring-slate-200';
                },

                entidadClass(entidad) {
                    const e = String(entidad || '').toLowerCase();
                    if (e.includes('ticket')) return 'bg-indigo-50 text-indigo-700 ring-indigo-200';
                    if (e.includes('venta')) return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
                    if (e.includes('producto') || e.includes('stock') || e.includes('invent')) return 'bg-sky-50 text-sky-700 ring-sky-200';
                    if (e.includes('cliente')) return 'bg-teal-50 text-teal-700 ring-teal-200';
                    if (e.includes('usuario')) return 'bg-violet-50 text-violet-700 ring-violet-200';
                    if (e.includes('perm') || e.includes('rol')) return 'bg-amber-50 text-amber-700 ring-amber-200';
                    if (e.includes('config')) return 'bg-slate-100 text-slate-700 ring-slate-200';
                    if (e.includes('agenda')) return 'bg-cyan-50 text-cyan-700 ring-cyan-200';
                    if (e.includes('dispositivo') || e.includes('instal')) return 'bg-fuchsia-50 text-fuchsia-700 ring-fuchsia-200';
                    return 'bg-slate-100 text-slate-700 ring-slate-200';
                },

                refHint(entidad, entidadId) {
                    const e = String(entidad || '').trim();
                    const id = String(entidadId || '').trim();
                    if (!e || !id) return '';
                    return `Entidad = ${e} → Ref. = ${id} significa “${e} id=${id}”.`;
                },

                prettyPayload(p) {
                    if (!p) return '—';
                    try {
                        if (typeof p === 'object') return JSON.stringify(p, null, 2);
                        const s = String(p);
                        const obj = JSON.parse(s);
                        return JSON.stringify(obj, null, 2);
                    } catch (e) {
                        return String(p);
                    }
                },

                payloadSize(p) {
                    if (!p) return '';
                    const s = (typeof p === 'string') ? p : JSON.stringify(p);
                    const kb = Math.max(1, Math.round((s.length / 1024) * 10) / 10);
                    return `${kb} KB`;
                },
            }
        }
    </script>
</x-app-layout>
