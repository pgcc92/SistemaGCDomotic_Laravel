@php($branding = app(\App\Domain\Tenant\TenantContext::class)->branding())
@php($logo = file_url($branding->logoUrl))

<!-- Mobile overlay -->
<div x-show="sidebarOpen" class="relative z-50 lg:hidden" x-cloak>
    <div x-show="sidebarOpen"
         x-transition.opacity
         class="fixed inset-0 bg-slate-950/45 backdrop-blur-sm"
         @click="sidebarOpen = false"></div>

    <div x-show="sidebarOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed inset-y-0 left-0 flex w-80 max-w-[85vw] flex-col border-r border-slate-200 bg-white p-4 shadow-2xl">
        <div class="flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <div class="h-9 w-9 rounded-lg bg-primary/10 flex items-center justify-center overflow-hidden ring-1 ring-inset ring-primary/10">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="Logo" class="h-full w-full object-contain" />
                    @else
                        <span class="font-bold text-primary">GC</span>
                    @endif
                </div>
                <div class="leading-tight">
                    <div class="text-sm font-semibold text-slate-900">{{ $branding->sidebarName ?? $branding->systemName }}</div>
                    <div class="text-xs text-slate-500">Dashboard</div>
                </div>
            </a>
            <button class="rounded-md p-2 hover:bg-slate-100" @click="sidebarOpen = false" aria-label="Close sidebar">
                <svg class="h-6 w-6 text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto">
            @include('layouts.sidebar-nav')
        </div>
    </div>
</div>

<!-- Desktop sidebar -->
<aside class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col border-r border-slate-200 bg-white">
    <div class="px-6 py-5 border-b border-slate-200">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl bg-primary/10 flex items-center justify-center overflow-hidden ring-1 ring-inset ring-primary/10">
                @if ($logo)
                    <img src="{{ $logo }}" alt="Logo" class="h-full w-full object-contain" />
                @else
                    <span class="font-bold text-primary">GC</span>
                @endif
            </div>
            <div class="leading-tight">
                <div class="text-sm font-semibold text-slate-900">{{ $branding->sidebarName ?? $branding->systemName }}</div>
                <div class="text-xs text-slate-500">Dashboard</div>
            </div>
        </a>
    </div>

    <div class="flex-1 overflow-y-auto px-4 py-4">
        @include('layouts.sidebar-nav')
    </div>

    <div class="border-t border-slate-200 p-4 text-xs text-slate-500">
        <div class="flex items-center justify-between">
            <span>GC Domotic</span>
            <span class="text-slate-400">v0</span>
        </div>
    </div>
</aside>
