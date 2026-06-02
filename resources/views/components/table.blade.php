@props([
    'striped' => true,
])

<div {{ $attributes->merge(['class' => 'gc-card overflow-hidden']) }}>
    <div class="overflow-auto">
        <table class="min-w-full text-sm">
            {{ $slot }}
        </table>
    </div>
</div>

