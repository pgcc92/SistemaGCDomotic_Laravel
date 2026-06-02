<x-app-layout>
    <x-slot name="header">
        <div class="text-lg font-semibold text-slate-900">{{ ucfirst(str_replace('_',' ', $module)) }}</div>
    </x-slot>

    <div class="space-y-4"
         x-data="moduleTable({ module: @js($module), initial: @js($rows ?? []) })"
         x-init="init()">
        <div class="flex items-center justify-between">
            <div class="text-sm text-slate-700">
                Tabla conectada al endpoint <code class="px-1 py-0.5 rounded bg-slate-100">/{{ $module }}/data</code>.
            </div>
            <div class="text-xs text-slate-500">Vista genérica</div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3a6 6 0 104.472 10.03l2.249 2.249a1 1 0 001.414-1.414l-2.249-2.249A6 6 0 009 3zm-4 6a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input x-model.debounce.200ms="q" @input="page=0"
                       placeholder="Buscar…"
                       class="w-full rounded-xl border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-primary focus:ring-primary" />
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50" @click="reload()">Recargar</button>
            </div>
        </div>

        <x-table>
            <thead class="bg-slate-50/60">
                <tr class="text-left text-xs font-semibold text-slate-600">
                    <template x-for="c in columns" :key="c">
                        <th class="px-4 py-3 whitespace-nowrap" x-text="c"></th>
                    </template>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row, idx) in pagedRows" :key="idx">
                    <tr class="border-b border-slate-100 hover:bg-slate-50">
                        <template x-for="c in columns" :key="c">
                            <td class="px-4 py-3 text-slate-700 whitespace-nowrap" x-text="row?.[c] ?? '—'"></td>
                        </template>
                    </tr>
                </template>
                <tr x-show="!loading && filteredRows.length === 0">
                    <td class="px-4 py-10 text-center text-slate-500" :colspan="columns.length">Sin datos.</td>
                </tr>
            </tbody>
        </x-table>

        <x-pagination page="page" pages="pages"></x-pagination>
    </div>

    <script>
        function moduleTable({ module, initial }) {
            return {
                module,
                rows: Array.isArray(initial) ? initial : [],
                q: '',
                page: 0,
                perPage: 25,
                loading: false,

                init() {
                    if (!this.rows.length) this.reload();
                },

                get columns() {
                    const first = this.filteredRows[0] || this.rows[0] || {};
                    return Object.keys(first);
                },

                get filteredRows() {
                    const q = (this.q || '').trim().toLowerCase();
                    if (!q) return this.rows;
                    return this.rows.filter(r => JSON.stringify(r).toLowerCase().includes(q));
                },

                get pages() {
                    return Math.max(1, Math.ceil(this.filteredRows.length / this.perPage));
                },

                get pagedRows() {
                    const start = this.page * this.perPage;
                    return this.filteredRows.slice(start, start + this.perPage);
                },

                async reload() {
                    this.loading = true;
                    try {
                        const res = await window.axios.get(`/${this.module}/data`, { headers: { 'Accept': 'application/json' } });
                        this.rows = res.data?.data || [];
                        this.page = 0;
                    } finally {
                        this.loading = false;
                    }
                },
            }
        }
    </script>
</x-app-layout>
