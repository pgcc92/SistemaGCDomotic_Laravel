<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <div class="text-lg font-semibold text-slate-900">Tickets</div>
            <div class="text-sm text-slate-500">Soporte, asignación y cierre de tickets.</div>
        </div>
    </x-slot>

    <div
        x-data="ticketsPage({
            urls: {
                data: '{{ route('tickets.data') }}',
                show: (tid) => `/tickets/${tid}`,
                asignar: (tid) => `/tickets/${tid}/asignar`,
                cerrar: (tid) => `/tickets/${tid}/cerrar`,
            }
        })"
        x-init="init()"
        class="space-y-6"
    >
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="gc-card p-5 bg-gradient-to-br from-sky-50 to-white">
                <div class="text-xs font-semibold text-sky-700/80">Abiertos</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.abiertos"></div>
                <div class="mt-1 text-xs text-slate-500">Requieren atención.</div>
            </div>
            <div class="gc-card p-5 bg-gradient-to-br from-amber-50 to-white">
                <div class="text-xs font-semibold text-amber-700/80">En progreso</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.progreso"></div>
                <div class="mt-1 text-xs text-slate-500">Asignados / seguimiento.</div>
            </div>
            <div class="gc-card p-5 bg-gradient-to-br from-emerald-50 to-white">
                <div class="text-xs font-semibold text-emerald-700/80">Cerrados</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.cerrados"></div>
                <div class="mt-1 text-xs text-slate-500">Finalizados.</div>
            </div>
            <div class="gc-card p-5 bg-gradient-to-br from-primary/10 to-white">
                <div class="text-xs font-semibold text-primary/80">Total visibles</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.total"></div>
                <div class="mt-1 text-xs text-slate-500">Según permisos.</div>
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
                       placeholder="Buscar por ticket, estado, teléfono, categoría…"
                       class="w-full rounded-xl border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-primary focus:ring-primary" />
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">Click para detalle</span>
            </div>
        </div>

        <div class="gc-card p-5">
            <x-table class="border-0 shadow-none">
                <thead class="bg-slate-50/60">
                    <tr class="text-left text-xs font-semibold text-slate-600">
                        <th class="px-4 py-3">Ticket</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Categoría</th>
                        <th class="px-4 py-3">Prioridad</th>
                        <th class="px-4 py-3">Actualizado</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in pagedRows" :key="row.ticket_id">
                        <tr class="border-b border-slate-100 hover:bg-slate-50 cursor-pointer"
                            @click="openDetail(row.ticket_id)">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900" x-text="row.ticket_id"></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900" x-text="row.cliente_wa"></div>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <template x-if="row.cliente_nombre">
                                        <span class="text-xs font-medium text-slate-600" x-text="row.cliente_nombre"></span>
                                    </template>
                                    <template x-if="row.cliente_razon_social">
                                        <span class="inline-flex items-center rounded-full bg-secondary/10 px-2 py-0.5 text-[11px] font-semibold text-secondary ring-1 ring-inset ring-secondary/15"
                                              x-text="row.cliente_razon_social"></span>
                                    </template>
                                    <template x-if="!row.cliente_nombre && !row.cliente_razon_social">
                                        <span class="text-xs text-slate-500" x-text="row.canal || '—'"></span>
                                    </template>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                      :class="badgeClass(row.estado)"
                                      x-text="row.estado || '—'"></span>
                            </td>
                            <td class="px-4 py-3 text-slate-700" x-text="row.categoria || '—'"></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                      :class="prioClass(row.prioridad)"
                                      x-text="row.prioridad || '—'"></span>
                            </td>
                            <td class="px-4 py-3 text-slate-600" x-text="fmtDate(row.updated_at)"></td>
                        </tr>
                    </template>
                    <tr x-show="!loading && filteredRows.length === 0">
                        <td class="px-4 py-10 text-center text-slate-500" colspan="6">No hay tickets.</td>
                    </tr>
                </tbody>
            </x-table>
        </div>

        <x-pagination page="page" pages="pages"></x-pagination>

        <!-- Detalle -->
        <x-modal name="ticket-detalle" maxWidth="4xl">
            <div class="border-b border-slate-200 px-6 py-4 bg-white/80 backdrop-blur">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="text-sm font-semibold text-slate-900 truncate" x-text="detail?.ticket?.ticket_id || 'Ticket'"></div>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                  :class="badgeClass(detail?.ticket?.estado)"
                                  x-text="detail?.ticket?.estado || '—'"></span>
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200"
                                  x-text="detail?.ticket?.categoria || '—'"></span>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                  :class="prioClass(detail?.ticket?.prioridad)"
                                  x-text="detail?.ticket?.prioridad || '—'"></span>
                        </div>
                        <div class="mt-0.5 text-xs text-slate-500">
                            <span class="font-semibold text-slate-700" x-text="detail?.ticket?.cliente_wa || ''"></span>
                            <span class="ms-2" x-text="fmtDate(detail?.ticket?.updated_at)"></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50"
                                @click="$dispatch('open-modal','ticket-asignar')">Asignar</button>
                        <button type="button" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60"
                                :disabled="typeof savingAction === 'undefined' ? false : !!savingAction"
                                @click="submitCerrar()"
                                x-text="(typeof savingAction === 'undefined' ? false : !!savingAction) ? 'Cerrando…' : 'Cerrar'"></button>
                        <x-icon-button @click="$dispatch('close-modal','ticket-detalle')" aria-label="Cerrar">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </x-icon-button>
                    </div>
                </div>
            </div>

            <div class="px-6 py-5">
                <template x-if="detailError">
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="detailError"></div>
                </template>

                <div class="grid gap-6 lg:grid-cols-12" x-show="detail">
                    <div class="space-y-4 lg:col-span-5">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="text-sm font-semibold text-slate-900">Resumen</div>
                            <div class="mt-4 space-y-3 text-sm">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="text-slate-500">Canal</div>
                                    <div class="text-right font-medium text-slate-900" x-text="detail?.ticket?.canal || '—'"></div>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <div class="text-slate-500">Técnico</div>
                                    <div class="text-right font-medium text-slate-900" x-text="tecnicoName(detail?.ticket?.tecnico_asignado)"></div>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <div class="text-slate-500">Última actualización</div>
                                    <div class="text-right font-medium text-slate-900" x-text="fmtDate(detail?.ticket?.updated_at)"></div>
                                </div>
                            </div>
                            <template x-if="detail?.ticket?.resumen">
                                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                                    <div class="text-xs font-semibold text-slate-600">Resumen</div>
                                    <div class="mt-1 whitespace-pre-wrap" x-text="detail?.ticket?.resumen"></div>
                                </div>
                            </template>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="text-sm font-semibold text-slate-900">Cliente</div>
                            <div class="mt-4 space-y-3 text-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="text-slate-500">Teléfono</div>
                                    <div class="font-medium text-slate-900" x-text="detail?.cliente?.telefono || detail?.ticket?.cliente_wa || '—'"></div>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <div class="text-slate-500">Nombre</div>
                                    <div class="font-medium text-slate-900" x-text="detail?.cliente?.nombre || '—'"></div>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <div class="text-slate-500">Dirección</div>
                                    <div class="font-medium text-slate-900 text-right" x-text="detail?.cliente?.direccion || '—'"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-7 space-y-4">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold text-slate-900">Chat</div>
                                <span class="text-xs text-slate-500" x-text="`${(chatRows().length)} mensajes`"></span>
                            </div>
                            <div class="mt-4 max-h-[56vh] overflow-auto pr-1">
                                <div class="space-y-3">
                                    <template x-for="row in chatRenderRows()" :key="row.key">
                                        <div>
                                            <template x-if="row.type === 'sep'">
                                                <div class="flex justify-center">
                                                    <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200" x-text="row.label"></div>
                                                </div>
                                            </template>
                                            <template x-if="row.type === 'msg'">
                                                <div class="flex" :class="row.m.from === 'tecnico' ? 'justify-end' : 'justify-start'">
                                                    <div class="relative max-w-[84%] sm:max-w-[72%] rounded-2xl px-4 py-3 shadow-sm ring-1"
                                                         :class="row.m.from === 'tecnico'
                                                                ? 'bg-primary text-white ring-primary/20 before:content-[\'\'] before:absolute before:-right-1 before:top-4 before:h-3 before:w-3 before:rotate-45 before:bg-primary before:rounded-sm'
                                                                : 'bg-slate-50 text-slate-900 ring-slate-200 before:content-[\'\'] before:absolute before:-left-1 before:top-4 before:h-3 before:w-3 before:rotate-45 before:bg-slate-50 before:rounded-sm'">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <div class="text-[11px] font-semibold opacity-80"
                                                                 x-text="row.m.from === 'tecnico' ? 'Técnico' : 'Cliente'"></div>
                                                            <div class="inline-flex items-center gap-2 text-[11px] opacity-75">
                                                                <span
                                                                    :title="String(row.m.created_at || row.m.createdAt || row.m.created || row.m.fecha || (row.m.created_at?.date) || (row.m.createdAt?.date) || '')"
                                                                    x-text="fmtTime(row.m.created_at || row.m.createdAt || row.m.created || row.m.fecha || (row.m.created_at?.date) || (row.m.createdAt?.date) || null)"></span>
                                                                <template x-if="row.m.from === 'tecnico'">
                                                                    <span class="inline-flex items-center" x-html="ticksHtml(row.m.estado_msg)"></span>
                                                                </template>
                                                            </div>
                                                        </div>

                                                        <template x-if="row.m.texto">
                                                            <div class="mt-1 whitespace-pre-wrap text-sm leading-relaxed" x-text="row.m.texto"></div>
                                                        </template>

                                                        <template x-if="row.m.media_url">
                                                            <div class="mt-2">
                                                                <template x-if="isImageUrl(row.m.media_url)">
                                                                    <a :href="row.m.media_url" target="_blank" rel="noreferrer"
                                                                       class="block overflow-hidden rounded-xl ring-1 ring-inset"
                                                                       :class="row.m.from === 'tecnico' ? 'ring-white/20' : 'ring-slate-200'">
                                                                        <img :src="row.m.media_url" class="max-h-56 w-full object-cover" alt="" />
                                                                    </a>
                                                                </template>
                                                                <template x-if="!isImageUrl(row.m.media_url)">
                                                                    <a class="text-sm font-semibold underline underline-offset-2"
                                                                       :class="row.m.from === 'tecnico' ? 'text-white/90 hover:text-white' : 'text-primary hover:text-primary/90'"
                                                                       :href="row.m.media_url" target="_blank" rel="noreferrer">
                                                                        Ver adjunto
                                                                    </a>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <div x-show="chatRows().length === 0" class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
                                        Sin mensajes para este ticket.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-modal>

        <!-- Asignar -->
        <x-modal name="ticket-asignar" maxWidth="2xl" focusable>
            <form class="divide-y divide-slate-200" @submit.prevent="submitAsignar()">
                <div class="px-6 py-4 flex items-start justify-between gap-3 bg-white/80 backdrop-blur">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Asignar técnico</div>
                        <div class="mt-0.5 text-xs text-slate-500">Se actualiza el técnico asignado del ticket.</div>
                    </div>
                    <x-icon-button @click="$dispatch('close-modal','ticket-asignar')" aria-label="Cerrar">
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
                        <label class="text-xs font-medium text-slate-700">Técnico</label>
                        <select x-model="assign.tecnico_id" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                            <option value="">— Seleccionar —</option>
                            <template x-for="t in (detail?.tecnicos_activos || [])" :key="t.id">
                                <option :value="t.id" x-text="t.nombre"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-700">Comentario (opcional)</label>
                        <input x-model="assign.comentario" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                    </div>
                </div>
                <div class="px-6 py-4 flex items-center justify-end gap-2 bg-white/80 backdrop-blur">
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50" @click="$dispatch('close-modal','ticket-asignar')">Cancelar</button>
                    <button class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90 disabled:opacity-60" :disabled="typeof savingAction === 'undefined' ? false : !!savingAction" x-text="(typeof savingAction === 'undefined' ? false : !!savingAction) ? 'Asignando…' : 'Asignar'"></button>
                </div>
            </form>
        </x-modal>
    </div>

    <script>
        function ticketsPage({ urls }) {
            return {
                urls,
                q: '',
                rows: [],
                loading: false,
                detail: null,
                detailError: '',
                assign: { tecnico_id: '', comentario: '' },
                actionError: '',
                savingAction: false,
                page: 0,
                perPage: 25,

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

                get filteredRows() {
                    const q = (this.q || '').trim().toLowerCase();
                    if (!q) return this.rows;
                    return this.rows.filter(r =>
                        String(r.ticket_id || '').toLowerCase().includes(q) ||
                        String(r.cliente_wa || '').toLowerCase().includes(q) ||
                        String(r.estado || '').toLowerCase().includes(q) ||
                        String(r.categoria || '').toLowerCase().includes(q) ||
                        String(r.prioridad || '').toLowerCase().includes(q) ||
                        String(r.asunto || '').toLowerCase().includes(q)
                    );
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

                get kpis() {
                    const rows = this.filteredRows || [];
                    const is = (r, v) => String(r?.estado || '').toUpperCase() === v;
                    const abiertos = rows.filter(r => is(r, 'ABIERTO')).length;
                    const cerrados = rows.filter(r => is(r, 'CERRADO')).length;
                    const progreso = rows.length - abiertos - cerrados;
                    return { abiertos, cerrados, progreso, total: rows.length };
                },

                async openDetail(tid) {
                    this.detailError = '';
                    this.detail = null;
                    this.actionError = '';
                    this.assign = { tecnico_id: '', comentario: '' };
                    this.$dispatch('open-modal', 'ticket-detalle');
                    try {
                        const res = await window.axios.get(this.urls.show(tid), { headers: { 'Accept': 'application/json' } });
                        this.detail = res.data?.data || null;
                        if (res.data?.ok !== true) {
                            this.detailError = res.data?.error || 'No se pudo cargar.';
                        }
                    } catch (e) {
                        this.detailError = e?.response?.data?.error || e?.message || 'Error';
                    }
                },

                async submitAsignar() {
                    this.actionError = '';
                    this.savingAction = true;
                    try {
                        const tid = this.detail?.ticket?.ticket_id;
                        const res = await window.axios.post(this.urls.asignar(tid), {
                            tecnico_id: Number(this.assign.tecnico_id),
                            comentario: this.assign.comentario || null,
                        }, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.actionError = res.data?.error || 'No se pudo asignar.';
                            return;
                        }
                        this.$dispatch('close-modal', 'ticket-asignar');
                        await this.reload();
                        await this.openDetail(tid);
                        window.GCToast?.success?.('Ticket asignado');
                    } catch (e) {
                        this.actionError = e?.response?.data?.error || e?.message || 'Error';
                    } finally {
                        this.savingAction = false;
                    }
                },

                async submitCerrar() {
                    this.actionError = '';
                    this.savingAction = true;
                    try {
                        const tid = this.detail?.ticket?.ticket_id;
                        const res = await window.axios.post(this.urls.cerrar(tid), {}, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.actionError = res.data?.error || 'No se pudo cerrar.';
                            window.GCToast?.error?.(this.actionError);
                            return;
                        }
                        await this.reload();
                        await this.openDetail(tid);
                        window.GCToast?.success?.('Ticket cerrado');
                    } catch (e) {
                        this.actionError = e?.response?.data?.error || e?.message || 'Error';
                        window.GCToast?.error?.(this.actionError);
                    } finally {
                        this.savingAction = false;
                    }
                },

                badgeClass(estado) {
                    const v = String(estado || '').toUpperCase();
                    if (v === 'CERRADO') return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
                    if (v === 'ABIERTO') return 'bg-sky-50 text-sky-700 ring-sky-200';
                    return 'bg-amber-50 text-amber-700 ring-amber-200';
                },

                prioClass(v) {
                    const s = String(v || '').toUpperCase();
                    // Heatmap: más urgencia => más "calor"
                    if (s === 'URGENTE') return 'bg-rose-600 text-white ring-rose-700';
                    if (s === 'ALTA') return 'bg-rose-50 text-rose-700 ring-rose-200';
                    if (s === 'MEDIA') return 'bg-amber-50 text-amber-700 ring-amber-200';
                    if (s === 'BAJA') return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
                    return 'bg-slate-100 text-slate-700 ring-slate-200';
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

                tecnicoName(id) {
                    const tid = Number(id || 0);
                    if (!tid) return '—';
                    const list = this.detail?.tecnicos_activos || [];
                    const t = list.find(x => Number(x.id) === tid);
                    return t?.nombre || `#${tid}`;
                },

                chatRows() {
                    const chat = this.detail?.chat;
                    if (Array.isArray(chat) && chat.length) return chat;
                    const msgs = this.detail?.mensajes || [];
                    return Array.isArray(msgs)
                        ? msgs.map(m => ({
                            id: m.id,
                            from: 'cliente',
                            estado_msg: null,
                            texto: m.texto || null,
                            media_url: m.media_url || null,
                            created_at: m.created_at || null,
                        }))
                        : [];
                },

                chatRenderRows() {
                    const items = this.chatRows() || [];
                    const out = [];
                    let last = null;
                    for (const m of items) {
                        const day = this.fmtDay(m.created_at);
                        if (day && day !== last) {
                            out.push({ type: 'sep', key: `sep:${day}`, label: day });
                            last = day;
                        }
                        out.push({ type: 'msg', key: `msg:${m.id}`, m });
                    }
                    return out;
                },

                fmtDay(v) {
                    if (!v) return null;
                    // Si ya viene como Date (por alguna normalización previa), úsalo tal cual.
                    const d = (v instanceof Date) ? v : this.parseDate(v);
                    if (!d) return null;
                    const dd = String(d.getDate()).padStart(2, '0');
                    const mm = String(d.getMonth() + 1).padStart(2, '0');
                    const yyyy = d.getFullYear();
                    return `${dd}/${mm}/${yyyy}`;
                },

                // WhatsApp-like: muestra fecha + hora en cada burbuja (dd/mm hh:mm)
                fmtTime(v) {
                    // 1) Si ya viene como Date, no lo rompas.
                    if (v instanceof Date) {
                        const dd = String(v.getDate()).padStart(2, '0');
                        const mm = String(v.getMonth() + 1).padStart(2, '0');
                        const hh = String(v.getHours()).padStart(2, '0');
                        const mi = String(v.getMinutes()).padStart(2, '0');
                        return `${dd}/${mm} ${hh}:${mi}`;
                    }
                    // 2) Normaliza posibles shapes (por si llega como objeto tipo {date: "..."} )
                    if (v && typeof v === 'object') v = v.date || v.datetime || v.value || v.iso || null;
                    // 3) Epoch en segundos o ms
                    if (typeof v === 'number' && Number.isFinite(v)) {
                        const ms = v < 1e12 ? (v * 1000) : v;
                        const d = new Date(ms);
                        if (!Number.isNaN(d.getTime())) {
                            const dd = String(d.getDate()).padStart(2, '0');
                            const mm = String(d.getMonth() + 1).padStart(2, '0');
                            const hh = String(d.getHours()).padStart(2, '0');
                            const mi = String(d.getMinutes()).padStart(2, '0');
                            return `${dd}/${mm} ${hh}:${mi}`;
                        }
                    }
                    // Primero: formato robusto por regex (no depende de Date()).
                    const s = String(v || '').trim();
                    const m = s.match(/(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
                    if (m) return `${m[3]}/${m[2]} ${m[4]}:${m[5]}`;
                    // Formato ya formateado: dd/mm/yyyy hh:mm
                    const m2 = s.match(/(\d{2})\/(\d{2})\/(\d{4}).*?(\d{2}):(\d{2})/);
                    if (m2) return `${m2[1]}/${m2[2]} ${m2[4]}:${m2[5]}`;

                    // Fallback: intenta parsear como Date (por si llega en otro formato).
                    const d = this.parseDate(v);
                    if (!d) return '—';
                    const dd = String(d.getDate()).padStart(2, '0');
                    const mm = String(d.getMonth() + 1).padStart(2, '0');
                    const hh = String(d.getHours()).padStart(2, '0');
                    const mi = String(d.getMinutes()).padStart(2, '0');
                    return `${dd}/${mm} ${hh}:${mi}`;
                },

                parseDate(v) {
                    if (!v) return null;
                    let s = String(v).trim();
                    // Postgres puede venir como:
                    // - "2026-05-08 04:45:40.546577+00"
                    // - "2026-05-08 04:45:40+00:00"
                    // - "2026-05-08 04:45:40"
                    // Normalizamos para que Date() lo parsee consistente.
                    s = s.replace(' ', 'T');
                    // +00 (sin minutos) => +00:00
                    s = s.replace(/([+-]\\d{2})$/, '$1:00');
                    // +0000 => +00:00
                    s = s.replace(/([+-]\\d{2})(\\d{2})$/, '$1:$2');
                    // +00:00 => Z (más compatible)
                    s = s.replace(/\\+00:00$/, 'Z');
                    let d = new Date(s);
                    if (!Number.isNaN(d.getTime())) return d;
                    // Fallback 1: quita microsegundos (a veces rompe en algunos browsers)
                    const noMicros = s.replace(/\\.(\\d{6})(Z|[+-].*)$/, '.$1$2').replace(/\\.(\\d{4,6})(Z|[+-].*)$/, '.$1$2').replace(/\\.(\\d{4,6})(?=Z$)/, '');
                    d = new Date(noMicros);
                    if (!Number.isNaN(d.getTime())) return d;
                    // Fallback 2: quita todo lo decimal
                    const noDecimals = s.replace(/\\.\\d+/, '');
                    d = new Date(noDecimals);
                    return Number.isNaN(d.getTime()) ? null : d;
                },

                isImageUrl(url) {
                    const u = String(url || '');
                    return /\\.(png|jpe?g|webp)(\\?.*)?$/i.test(u);
                },

                ticksHtml(estado) {
                    const s = String(estado || '').toLowerCase();
                    // enviado | entregado | leido (otros => simple)
                    const color = s === 'leido' ? 'text-sky-200' : 'text-white/70';
                    const two = (s === 'entregado' || s === 'leido');
                    const svg = (extra) => `
                        <svg class="h-3.5 w-3.5 ${color}" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.415l-7.25 7.25a1 1 0 01-1.414 0l-3.25-3.25a1 1 0 011.414-1.414l2.543 2.543 6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>`;
                    return two ? `<span class="inline-flex -space-x-1">${svg()}${svg()}</span>` : svg();
                },
            }
        }
    </script>
</x-app-layout>
