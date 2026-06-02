@props([
    'variant' => 'ghost', // ghost|solid
])

@php
    $base = 'inline-flex items-center justify-center rounded-lg p-2 text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/40';
    $cls = $variant === 'solid'
        ? 'bg-primary text-white hover:bg-primary/90 focus:ring-primary/40'
        : $base;
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => $cls]) }}>
    {{ $slot }}
</button>

