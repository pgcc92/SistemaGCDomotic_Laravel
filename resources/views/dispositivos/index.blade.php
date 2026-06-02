<x-app-layout>
    <x-slot name="header">
        <div class="text-lg font-semibold text-slate-900">Dispositivos / Instalaciones</div>
    </x-slot>

    <div
        x-data="dispositivosPage({
            urls: {
                data: '{{ route('dispositivos.data') }}',
                show: (id) => `/dispositivos/${id}`,
                create: '{{ route('dispositivos.store') }}',
                clientes: '{{ route('clientes.data') }}',
                productos: '{{ route('productos.data') }}',
            }
        })"
        x-init="init()"
        class="space-y-6"
    >
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="gc-card p-5 bg-gradient-to-br from-primary/10 to-white">
                <div class="text-xs font-semibold text-primary/80">Instalaciones (total)</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.total"></div>
                <div class="mt-1 text-xs text-slate-500">Registros visibles.</div>
            </div>
            <div class="gc-card p-5 bg-gradient-to-br from-emerald-50 to-white">
                <div class="text-xs font-semibold text-emerald-700/80">Este mes</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.mes"></div>
                <div class="mt-1 text-xs text-slate-500">Instalaciones registradas.</div>
            </div>
            <div class="gc-card p-5 bg-gradient-to-br from-sky-50 to-white">
                <div class="text-xs font-semibold text-sky-700/80">Hoy</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.hoy"></div>
                <div class="mt-1 text-xs text-slate-500">Nuevas instalaciones.</div>
            </div>
            <div class="gc-card p-5 bg-gradient-to-br from-amber-50 to-white">
                <div class="text-xs font-semibold text-amber-700/80">Con GPS</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900" x-text="kpis.conGps"></div>
                <div class="mt-1 text-xs text-slate-500">Lat/Lng registrados.</div>
            </div>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3a6 6 0 104.472 10.03l2.249 2.249a1 1 0 001.414-1.414l-2.249-2.249A6 6 0 009 3zm-4 6a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input x-model.debounce.200ms="q" @input="page=0"
                       placeholder="Buscar por WhatsApp, modelo o serial…"
                       class="w-full rounded-xl border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-primary focus:ring-primary" />
            </div>
            <div class="flex items-center gap-2">
                <button type="button"
                        class="rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-white hover:bg-primary/90"
                        @click="$dispatch('open-modal','dispositivo-form'); resetForm()">
                    Nueva instalación
                </button>
            </div>
        </div>

        <x-table>
            <thead class="bg-slate-50/60">
                <tr class="text-left text-xs font-semibold text-slate-600">
                    <th class="px-4 py-3">Foto</th>
                    <th class="px-4 py-3">Cliente (WA)</th>
                    <th class="px-4 py-3">Modelo</th>
                    <th class="px-4 py-3">Serial</th>
                    <th class="px-4 py-3">Instalación</th>
                    <th class="px-4 py-3">Actualizado</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="row in pagedRows" :key="row.id">
                    <tr class="border-b border-slate-100 hover:bg-slate-50 cursor-pointer" @click="openDetail(row.id)">
                        <td class="px-4 py-3">
                            <template x-if="row.foto_thumb_url || row.foto_url">
                                <img :src="fileUrl(row.foto_thumb_url || row.foto_url)"
                                     class="h-9 w-12 rounded-lg object-cover ring-1 ring-slate-200 bg-slate-100" alt="" />
                            </template>
                            <template x-if="!(row.foto_thumb_url || row.foto_url)">
                                <div class="h-9 w-12 rounded-lg bg-slate-100 ring-1 ring-slate-200"></div>
                            </template>
                        </td>
                        <td class="px-4 py-3 font-medium text-slate-900" x-text="row.cliente_wa || '—'"></td>
                        <td class="px-4 py-3 text-slate-700" x-text="row.modelo_cerradura || '—'"></td>
                        <td class="px-4 py-3 text-slate-700" x-text="row.serial_cerradura || '—'"></td>
                        <td class="px-4 py-3 text-slate-700" x-text="row.fecha_instalacion || '—'"></td>
                        <td class="px-4 py-3 text-slate-600" x-text="row.creado_en || row.created_at || '—'"></td>
                    </tr>
                </template>
                <tr x-show="!loading && filteredRows.length === 0">
                    <td class="px-4 py-10 text-center text-slate-500" colspan="6">No hay instalaciones.</td>
                </tr>
            </tbody>
        </x-table>

        <x-pagination page="page" pages="pages"></x-pagination>

        <!-- Detalle -->
        <x-modal name="dispositivo-detalle" maxWidth="4xl">
            <div class="border-b border-slate-200 px-6 py-4 flex items-start justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Detalle de instalación</div>
                    <div class="mt-0.5 text-xs text-slate-500" x-text="detail?.dispositivo?.cliente_wa ? `Cliente: ${detail.dispositivo.cliente_wa}` : '—'"></div>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">Instalación</span>
                        <template x-if="detail?.dispositivo?.gps_lat && detail?.dispositivo?.gps_lng">
                            <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200">Con GPS</span>
                        </template>
                        <template x-if="detail?.dispositivo?.instalador_nombre">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200" x-text="`Técnico: ${detail.dispositivo.instalador_nombre}`"></span>
                        </template>
                    </div>
                </div>
                <x-icon-button @click="$dispatch('close-modal','dispositivo-detalle')" aria-label="Cerrar">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </x-icon-button>
            </div>
            <div class="p-6 space-y-5">
                <template x-if="detailError">
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="detailError"></div>
                </template>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                    <div class="lg:col-span-5 space-y-3 rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-xs font-semibold text-slate-700">Foto principal</div>
                            <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-500 ring-1 ring-inset ring-slate-200" x-text="`${(detail?.fotos || []).length} foto(s)`"></span>
                        </div>
                        <template x-if="detail?.dispositivo?.foto_url">
                            <img :src="fileUrl(detail.dispositivo.foto_url)"
                                 class="w-full aspect-[4/3] rounded-2xl object-cover ring-1 ring-slate-200 bg-white" alt="" />
                        </template>
                        <template x-if="!detail?.dispositivo?.foto_url">
                            <div class="flex w-full aspect-[4/3] items-center justify-center rounded-2xl bg-white text-sm text-slate-400 ring-1 ring-slate-200">Sin foto</div>
                        </template>
                    </div>
                    <div class="lg:col-span-7">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="text-xs font-medium text-slate-700">Modelo</div>
                                <div class="mt-1 text-sm text-slate-900" x-text="detail?.dispositivo?.modelo_cerradura || '—'"></div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="text-xs font-medium text-slate-700">Serial</div>
                                <div class="mt-1 text-sm text-slate-900" x-text="detail?.dispositivo?.serial_cerradura || '—'"></div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:col-span-2">
                                <div class="text-xs font-medium text-slate-700">Dirección</div>
                                <div class="mt-1 text-sm text-slate-900 whitespace-pre-wrap" x-text="detail?.dispositivo?.direccion || '—'"></div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="text-xs font-medium text-slate-700">Fecha instalación</div>
                                <div class="mt-1 text-sm text-slate-900" x-text="detail?.dispositivo?.fecha_instalacion || '—'"></div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="text-xs font-medium text-slate-700">Técnico</div>
                                <div class="mt-1 text-sm font-medium text-slate-900" x-text="instaladorText(detail?.dispositivo)"></div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="text-xs font-medium text-slate-700">GPS</div>
                                <div class="mt-1 text-sm text-slate-900" x-text="gpsText(detail?.dispositivo)"></div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:col-span-2">
                                <div class="text-xs font-medium text-slate-700">Notas</div>
                                <div class="mt-1 text-sm text-slate-900 whitespace-pre-wrap" x-text="detail?.dispositivo?.notas_instalacion || '—'"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-medium text-slate-700">Galería</div>
                        <div class="text-xs text-slate-500" x-text="`${(detail?.fotos || []).length} foto(s)`"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        <template x-for="f in (detail?.fotos || [])" :key="f.id || f.url">
                            <button type="button" class="block text-left" @click="openGallery(f)">
                                <img :src="fileUrl(f.thumb_url || f.url)"
                                     class="w-full aspect-[4/3] rounded-xl object-cover ring-1 ring-slate-200 bg-slate-100 hover:opacity-90" alt="" />
                            </button>
                        </template>
                        <template x-if="(detail?.fotos || []).length === 0">
                            <div class="col-span-full rounded-xl border border-dashed border-slate-200 bg-white p-6 text-sm text-slate-500">
                                Sin fotos adicionales.
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </x-modal>

        <!-- Galería (slider) -->
        <div x-show="galleryOpen" x-cloak class="fixed inset-0 z-[120]">
            <div class="absolute inset-0 bg-slate-950/70" @click="galleryOpen=false"></div>
            <div class="absolute inset-0 flex items-center justify-center p-4">
                <div class="w-full max-w-5xl overflow-hidden rounded-2xl border border-white/10 bg-slate-950/60 backdrop-blur">
                    <div class="flex items-center justify-between gap-2 px-4 py-3 border-b border-white/10">
                        <div class="text-sm font-semibold text-white/90" x-text="galleryTitle()"></div>
                        <button type="button" class="rounded-lg p-2 text-white/80 hover:bg-white/10" @click="galleryOpen=false" aria-label="Cerrar">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="relative">
                        <button type="button" class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-2 text-white hover:bg-white/15"
                                @click="galleryPrev()" aria-label="Anterior">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-2 text-white hover:bg-white/15"
                                @click="galleryNext()" aria-label="Siguiente">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                        <div class="p-4">
                            <div class="mx-auto w-full max-w-4xl aspect-[16/10] rounded-2xl bg-black/20 ring-1 ring-inset ring-white/10 overflow-hidden flex items-center justify-center">
                                <img :src="fileUrl(galleryCurrent()?.url)" alt="" class="h-full w-full object-contain" />
                            </div>
                        </div>
                    </div>
                    <div class="px-4 pb-4">
                        <div class="flex items-center justify-between text-xs text-white/60">
                            <div x-text="galleryMeta()"></div>
                            <a class="text-white/80 hover:text-white underline underline-offset-2" :href="fileUrl(galleryCurrent()?.url)" target="_blank" rel="noreferrer">Abrir original</a>
                        </div>
                        <div class="mt-3 flex gap-2 overflow-auto">
                            <template x-for="(f, idx) in (detail?.fotos || [])" :key="f.id || f.url">
                                <button type="button" class="shrink-0 rounded-xl ring-1 ring-inset"
                                        :class="idx===galleryIndex ? 'ring-white/60' : 'ring-white/10'"
                                        @click="galleryIndex = idx">
                                    <img :src="fileUrl(f.thumb_url || f.url)" class="h-16 w-24 object-cover rounded-xl" alt="" />
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Crear -->
        <x-modal name="dispositivo-form" maxWidth="3xl">
            <form class="divide-y divide-slate-200" @submit.prevent="submitForm()">
                <div class="px-6 py-4 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Nueva instalación</div>
                        <div class="mt-0.5 text-xs text-slate-500">Registra instalación y sube foto (opcional).</div>
                    </div>
                    <x-icon-button @click="$dispatch('close-modal','dispositivo-form')" aria-label="Cerrar">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </x-icon-button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <template x-if="formError">
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" x-text="formError"></div>
                    </template>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-medium text-slate-700">Cliente (WhatsApp)</label>
                            <div class="relative mt-1">
                                <input x-model.debounce.200ms="form.cliente_wa" @input="searchClientes()"
                                       class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                                       placeholder="Buscar por teléfono o nombre…" />
                                <div x-show="cliOpen" x-cloak class="absolute z-20 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
                                    <div class="max-h-64 overflow-auto">
                                        <template x-for="c in cliResults" :key="c.id">
                                            <button type="button" class="w-full px-4 py-3 text-left hover:bg-slate-50"
                                                    @click="pickCliente(c)">
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <div class="text-sm font-semibold text-slate-900 truncate" x-text="c.telefono"></div>
                                                        <div class="text-xs text-slate-500 truncate" x-text="c.nombre || c.razon_social || '—'"></div>
                                                    </div>
                                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">cliente</span>
                                                </div>
                                            </button>
                                        </template>
                                        <div x-show="cliResults.length===0" class="px-4 py-6 text-sm text-slate-500">Sin resultados.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Fecha instalación</label>
                            <input x-model="form.fecha_instalacion" type="date" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Modelo</label>
                            <div class="relative mt-1">
                                <input x-model.debounce.200ms="form.modelo_cerradura" @input="searchModelos()"
                                       class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                                       placeholder="Buscar por SKU, nombre o modelo…" />
                                <div x-show="modOpen" x-cloak class="absolute z-20 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
                                    <div class="max-h-64 overflow-auto">
                                        <template x-for="p in modResults" :key="p.id">
                                            <button type="button" class="w-full px-4 py-3 text-left hover:bg-slate-50"
                                                    @click="pickModelo(p)">
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <div class="text-sm font-semibold text-slate-900 truncate" x-text="p.modelo || p.nombre"></div>
                                                        <div class="text-xs text-slate-500 truncate" x-text="`${p.sku || ''} ${p.nombre || ''}`.trim()"></div>
                                                    </div>
                                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">producto</span>
                                                </div>
                                            </button>
                                        </template>
                                        <div x-show="modResults.length===0" class="px-4 py-6 text-sm text-slate-500">Sin resultados.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">Serial</label>
                            <input x-model="form.serial_cerradura" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Dirección</label>
                            <textarea x-model="form.direccion" rows="2" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"></textarea>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">GPS Lat</label>
                            <input x-model="form.gps_lat" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" placeholder="-12.0" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-700">GPS Lng</label>
                            <input x-model="form.gps_lng" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary" placeholder="-77.0" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Notas</label>
                            <textarea x-model="form.notas_instalacion" rows="3" class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-primary"></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Fotos (máx. 5, JPG/PNG/WebP, 10MB c/u)</label>
                            <input type="file" accept="image/jpeg,image/png,image/webp" capture="environment" multiple x-ref="fotoFile"
                                   class="mt-1 w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm" />
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50" @click="$dispatch('close-modal','dispositivo-form')">Cancelar</button>
                    <button class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90" :disabled="saving" x-text="saving ? 'Guardando…' : 'Guardar'"></button>
                </div>
            </form>
        </x-modal>
    </div>

    <script>
        function dispositivosPage({ urls }) {
            return {
                urls,
                q: '',
                rows: [],
                loading: false,
                page: 0,
                perPage: 25,

                detail: null,
                detailError: '',
                galleryOpen: false,
                galleryIndex: 0,

                form: { cliente_wa: '', modelo_cerradura: '', serial_cerradura: '', direccion: '', fecha_instalacion: '', gps_lat: '', gps_lng: '', notas_instalacion: '' },
                formError: '',
                saving: false,
                cliOpen: false,
                cliResults: [],
                cliCache: [],
                cliCacheAt: 0,
                modOpen: false,
                modResults: [],
                modCache: [],
                modCacheAt: 0,

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

                get kpis() {
                    const rows = this.filteredRows || [];
                    const now = new Date();
                    const sameMonth = (v) => {
                        if (!v) return false;
                        const d = new Date(String(v).replace(' ', 'T'));
                        return !Number.isNaN(d.getTime()) && d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth();
                    };
                    const sameDay = (v) => {
                        if (!v) return false;
                        const d = new Date(String(v).replace(' ', 'T'));
                        return !Number.isNaN(d.getTime()) && d.toDateString() === now.toDateString();
                    };
                    const total = rows.length;
                    const mes = rows.filter(r => sameMonth(r.creado_en || r.created_at)).length;
                    const hoy = rows.filter(r => sameDay(r.creado_en || r.created_at)).length;
                    const conGps = rows.filter(r => (r.gps_lat !== null && r.gps_lat !== '' && r.gps_lng !== null && r.gps_lng !== '')).length;
                    return { total, mes, hoy, conGps };
                },

                async searchClientes() {
                    const q = String(this.form.cliente_wa || '').trim();
                    if (q.length < 2) { this.cliOpen = false; this.cliResults = []; return; }
                    try {
                        const res = await window.axios.get(this.urls.clientes, {
                            headers: { 'Accept': 'application/json' },
                            params: { q, limit: 20 },
                        });
                        const rows = res.data?.data || [];
                        this.cliResults = (Array.isArray(rows) ? rows : []).slice(0, 12);
                        this.cliOpen = true;
                    } catch {
                        this.cliOpen = false;
                    }
                },

                pickCliente(c) {
                    this.form.cliente_wa = String(c.telefono || '');
                    this.cliOpen = false;
                },

                async searchModelos() {
                    const q = String(this.form.modelo_cerradura || '').trim();
                    if (q.length < 2) { this.modOpen = false; this.modResults = []; return; }
                    try {
                        const res = await window.axios.get(this.urls.productos, {
                            headers: { 'Accept': 'application/json' },
                            params: { q, limit: 20 },
                        });
                        const rows = res.data?.data || [];
                        this.modResults = (Array.isArray(rows) ? rows : []).slice(0, 12);
                        this.modOpen = true;
                    } catch {
                        this.modOpen = false;
                    }
                },

                pickModelo(p) {
                    this.form.modelo_cerradura = String(p.modelo || p.nombre || '');
                    this.modOpen = false;
                },

                get filteredRows() {
                    const q = (this.q || '').trim().toLowerCase();
                    if (!q) return this.rows;
                    return this.rows.filter(r =>
                        String(r.cliente_wa || '').toLowerCase().includes(q) ||
                        String(r.modelo_cerradura || '').toLowerCase().includes(q) ||
                        String(r.serial_cerradura || '').toLowerCase().includes(q)
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
                    this.formError = '';
                    this.form = { cliente_wa: '', modelo_cerradura: '', serial_cerradura: '', direccion: '', fecha_instalacion: '', gps_lat: '', gps_lng: '', notas_instalacion: '' };
                    if (this.$refs.fotoFile) this.$refs.fotoFile.value = '';
                },

                async openDetail(id) {
                    this.detail = null;
                    this.detailError = '';
                    this.$dispatch('open-modal', 'dispositivo-detalle');
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

                openGallery(f) {
                    const fotos = this.detail?.fotos || [];
                    const idx = fotos.findIndex(x => (x.id && f.id && x.id === f.id) || (x.url && f.url && x.url === f.url));
                    this.galleryIndex = idx >= 0 ? idx : 0;
                    this.galleryOpen = true;
                },

                galleryCurrent() {
                    const fotos = this.detail?.fotos || [];
                    return fotos[this.galleryIndex] || null;
                },

                galleryPrev() {
                    const n = (this.detail?.fotos || []).length;
                    if (!n) return;
                    this.galleryIndex = (this.galleryIndex - 1 + n) % n;
                },

                galleryNext() {
                    const n = (this.detail?.fotos || []).length;
                    if (!n) return;
                    this.galleryIndex = (this.galleryIndex + 1) % n;
                },

                galleryTitle() {
                    const cur = this.galleryCurrent();
                    return cur?.tipo ? `Galería • ${cur.tipo}` : 'Galería';
                },

                galleryMeta() {
                    const n = (this.detail?.fotos || []).length;
                    if (!n) return '';
                    return `${this.galleryIndex + 1} / ${n}`;
                },

                async submitForm() {
                    this.formError = '';
                    this.saving = true;
                    try {
                        const fd = new FormData();
                        for (const [k, v] of Object.entries(this.form)) {
                            fd.append(k, v ?? '');
                        }
                        const files = Array.from(this.$refs.fotoFile?.files || []);
                        if (files.length > 5) {
                            this.formError = 'Puedes subir máximo 5 imágenes.';
                            return;
                        }
                        files.forEach((file) => fd.append('fotos[]', file));
                        if (files[0]) fd.append('foto', files[0]);

                        const res = await window.axios.post(this.urls.create, fd, { headers: { 'Accept': 'application/json' } });
                        if (res.data?.ok !== true) {
                            this.formError = res.data?.error || 'No se pudo guardar.';
                            return;
                        }
                        this.$dispatch('close-modal', 'dispositivo-form');
                        await this.reload();
                    } catch (e) {
                        this.formError = e?.response?.data?.error || e?.message || 'Error';
                    } finally {
                        this.saving = false;
                    }
                },

                gpsText(d) {
                    const lat = d?.gps_lat;
                    const lng = d?.gps_lng;
                    if (lat === null || lat === undefined || lng === null || lng === undefined) return '—';
                    return `${lat}, ${lng}`;
                },

                instaladorText(d) {
                    const nombre = String(d?.instalador_nombre || '').trim();
                    const doc = String(d?.instalador_documento || '').trim();
                    if (nombre && doc) return `${nombre} · ${doc}`;
                    return nombre || (d?.instalador_id ? `Usuario #${d.instalador_id}` : '—');
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
            }
        }
    </script>
</x-app-layout>
