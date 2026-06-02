@props([
    'page' => 'page',
    'pages' => 'pages',
])

<div class="flex items-center justify-between gap-3 py-3">
    <div class="text-xs text-slate-500">
        Página <span class="font-medium text-slate-700" x-text="{{ $page }} + 1"></span>
        de <span class="font-medium text-slate-700" x-text="{{ $pages }}"></span>
    </div>

    <div class="flex items-center gap-2">
        <button type="button"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50 disabled:opacity-50"
                :disabled="{{ $page }} <= 0"
                @click="{{ $page }} = Math.max(0, {{ $page }} - 1)">
            Anterior
        </button>
        <button type="button"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50 disabled:opacity-50"
                :disabled="{{ $page }} >= ({{ $pages }} - 1)"
                @click="{{ $page }} = Math.min({{ $pages }} - 1, {{ $page }} + 1)">
            Siguiente
        </button>
    </div>
</div>

