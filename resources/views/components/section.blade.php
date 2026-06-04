@props([
    'title' => null,
    'subtitle' => null,
    'actions' => null,
])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900']) }}>
    @if($title || $subtitle || $actions)
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-800">
            <div>
                @if($title)
                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</div>
                @endif
                @if($subtitle)
                    <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $subtitle }}</div>
                @endif
            </div>
            @if($actions)
                <div>{{ $actions }}</div>
            @endif
        </header>
    @endif

    <div class="p-4">
        {{ $slot }}
    </div>
</section>
