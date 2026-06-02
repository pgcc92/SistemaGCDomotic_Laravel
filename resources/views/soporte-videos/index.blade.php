<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <div class="text-lg font-semibold text-slate-900">Soporte videos</div>
            <div class="text-sm text-slate-500">Base de conocimiento por modelo/categoría con URL de solución.</div>
        </div>
    </x-slot>

    <div
        x-data="soporteVideosPage({
            urls: {
                data: '{{ route('soporte-videos.data') }}',
                show: (id) => `/soporte-videos/${id}`,
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
                       placeholder="Buscar por modelo, categoría, problema…"
                       class="w-full rounded-xl border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-primary focus:ring-primary" />
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">
                    Enlaces rápidos
                </span>
            </div>
        </div>

        <div class="gc-card p-5">
            <x-table class="border-0 shadow-none">
                <thead class="bg-slate-50/60">
                    <tr class="text-left text-xs font-semibold text-slate-600">
                        <th class="px-4 py-3">Modelo</th>
                        <th class="px-4 py-3">Categoría</th>
                        <th class="px-4 py-3">Problema</th>
                        <th class="px-4 py-3">URL</th>
                        <th class="px-4 py-3">Activo</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in pagedRows" :key="row.id">
                        <tr class="border-b border-slate-100 hover:bg-slate-50 cursor-pointer" @click="openDetail(row.id)">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900" x-text="row.modelo || row.modeloalt || '—'"></div>
                                <div class="text-xs text-slate-500" x-text="row.modeloalt && row.modelo ? row.modeloalt : ''"></div>
                            </td>
                            <td class="px-4 py-3 text-slate-700" x-text="row.categoria || '—'"></td>
                            <td class="px-4 py-3 text-slate-700">
                                <div class="line-clamp-2" x-text="row.problema || '—'"></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-lg bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">
                                        link
                                    </span>
                                    <span class="text-xs text-slate-600 line-clamp-1 max-w-[260px]" x-text="row.video_url || '—'"></span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                      :class="row.activo ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-700 ring-slate-200'"
                                      x-text="row.activo ? 'Sí' : 'No'"></span>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && filteredRows.length === 0">
                        <td class="px-4 py-10 text-center text-slate-500" colspan="5">No hay videos.</td>
                    </tr>
                </tbody>
            </x-table>
        </div>

        <x-pagination page="page" pages="pages"></x-pagination>

        <x-modal name="soporte-video-detalle" maxWidth="4xl">
            <div class="border-b border-slate-200 px-6 py-4 bg-white/80 backdrop-blur">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-slate-900 truncate" x-text="detail?.modelo || detail?.modeloalt || 'Soporte video'"></div>
                        <div class="mt-0.5 text-xs text-slate-500" x-text="detail?.categoria || ''"></div>
                    </div>
                    <x-icon-button @click="$dispatch('close-modal','soporte-video-detalle')" aria-label="Cerrar">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </x-icon-button>
                </div>
            </div>
            <div class="px-6 py-5 space-y-4">
                <template x-if="detailError">
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="detailError"></div>
                </template>

                <div class="grid gap-4 lg:grid-cols-12" x-show="detail">
                    <div class="lg:col-span-5 space-y-4">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="text-sm font-semibold text-slate-900">Problema</div>
                            <div class="mt-2 text-sm text-slate-700 whitespace-pre-wrap" x-text="detail?.problema || '—'"></div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="text-sm font-semibold text-slate-900">Solución</div>
                            <div class="mt-2 text-sm text-slate-700 whitespace-pre-wrap" x-text="detail?.solucion || '—'"></div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-sm font-semibold text-slate-900">URL</div>
                                <button type="button" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm hover:bg-slate-50"
                                        @click="copyUrl(detail?.video_url)">
                                    Copiar
                                </button>
                            </div>
                            <div class="mt-2 text-sm text-slate-700 break-all" x-text="detail?.video_url || '—'"></div>
                        </div>
                    </div>
                    <div class="lg:col-span-7">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5">
                            <div class="text-sm font-semibold text-slate-900">Vista previa</div>
                            <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <template x-if="detail?.video_url">
                                    <a class="text-sm font-semibold text-primary hover:underline" :href="detail.video_url" target="_blank" rel="noreferrer">
                                        Abrir video en nueva pestaña
                                    </a>
                                </template>
                                <template x-if="!detail?.video_url">
                                    <div class="text-sm text-slate-500">Sin URL.</div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-modal>
    </div>

    <script>
        function soporteVideosPage({ urls }) {
            return {
                urls,
                q: '',
                rows: [],
                loading: false,
                page: 0,
                perPage: 25,
                detail: null,
                detailError: '',

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
                    return this.rows.filter(r => JSON.stringify(r).toLowerCase().includes(q));
                },

                get pages() { return Math.max(1, Math.ceil(this.filteredRows.length / this.perPage)); },
                get pagedRows() {
                    const start = this.page * this.perPage;
                    return this.filteredRows.slice(start, start + this.perPage);
                },

                async openDetail(id) {
                    this.detailError = '';
                    this.detail = null;
                    this.$dispatch('open-modal', 'soporte-video-detalle');
                    try {
                        const res = await window.axios.get(this.urls.show(id), { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.detailError = res.data?.error || 'No se pudo cargar.';
                            return;
                        }
                        this.detail = res.data?.data || null;
                    } catch (e) {
                        this.detailError = e?.response?.data?.error || e?.message || 'Error';
                    }
                },

                async copyUrl(url) {
                    const v = String(url || '').trim();
                    if (!v) return;
                    try {
                        await navigator.clipboard.writeText(v);
                        window.GCToast?.success?.('Copiado', 'URL copiada al portapapeles.');
                    } catch {
                        window.GCToast?.error?.('No se pudo copiar');
                    }
                },
            }
        }
    </script>
</x-app-layout>

