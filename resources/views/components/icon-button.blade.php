@props([
    'variant' => 'ghost', // ghost|solid
])

@php
    $base = 'inline-flex items-center justify-center rounded-lg p-2 text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-primary/40 dark:text-slate-200 dark:hover:bg-slate-800';
    $cls = $variant === 'solid'
        ? 'bg-primary text-white hover:bg-primary/90 focus:ring-primary/40 dark:bg-primary dark:text-white dark:hover:bg-primary/90'
        : $base;
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => $cls]) }}>
    {{ $slot }}
</button>
