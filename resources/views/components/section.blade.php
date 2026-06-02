@props([
    'title' => null,
    'subtitle' => null,
    'actions' => null,
])

<section {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white']) }}>
    @if($title || $subtitle || $actions)
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <div>
                @if($title)
                    <div class="text-sm font-semibold text-slate-900">{{ $title }}</div>
                @endif
                @if($subtitle)
                    <div class="mt-0.5 text-xs text-slate-500">{{ $subtitle }}</div>
                @endif
            </div>
            @if($actions)
                <div>{{ $actions }}</div>
            @endif
        </header>
    @endif

    <div class="p-5">
        {{ $slot }}
    </div>
</section>

