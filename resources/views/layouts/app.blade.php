<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php($branding = app(\App\Domain\Tenant\TenantContext::class)->branding())
        <title>{{ $branding->systemName }}</title>
        @php($favicon = file_url($branding->faviconUrl))
        @if ($favicon)
            <link rel="icon" href="{{ $favicon }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600|inter:400,500,600|plus-jakarta-sans:400,500,600|dm-sans:400,500,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                @foreach($branding->cssVars() as $k => $v)
                    --{{ $k }}: {{ $v }};
                @endforeach
            }
        </style>
    </head>
    <body class="antialiased bg-slate-50 text-slate-900" style="font-family: var(--gc-font), ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;">
        <div x-data="{ sidebarOpen: false }"
             x-effect="document.documentElement.classList.toggle('overflow-hidden', sidebarOpen)"
             @keydown.escape.window="sidebarOpen = false"
             class="min-h-screen">
            @include('layouts.sidebar')

            <div class="lg:ps-64">
                <!-- Top bar -->
                <header class="sticky top-0 z-30 bg-white/80 backdrop-blur border-b border-slate-200">
                    <div class="flex items-center gap-3 px-4 py-3 lg:px-8">
                        <button type="button" class="lg:hidden rounded-md p-2 hover:bg-slate-100" @click="sidebarOpen = true" aria-label="Open sidebar">
                            <svg class="h-6 w-6 text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <div class="flex-1">
                            @if (isset($header))
                                {{ $header }}
                            @else
                                <div class="text-sm text-slate-600">{{ $branding->systemName }}</div>
                            @endif
                        </div>

                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                @php($initial = mb_strtoupper(mb_substr((string) Auth::user()->name, 0, 1)))
                                <button class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white p-1.5 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary/40"
                                        aria-label="Abrir menú de perfil">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                                        {{ $initial }}
                                    </span>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <div class="px-4 py-3">
                                    <div class="text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</div>
                                    <div class="text-xs text-slate-500">{{ Auth::user()->email }}</div>
                                </div>
                                <div class="border-t border-slate-100"></div>
                                <x-dropdown-link :href="route('profile.edit')">Perfil</x-dropdown-link>
                                <x-dropdown-link :href="route('configuracion.edit')">Configuración</x-dropdown-link>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault(); this.closest('form').submit();">
                                        Salir
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </header>

                <main class="px-3 pb-24 pt-4 sm:px-4 sm:py-6 lg:px-8 lg:pb-6">
                    {{ $slot }}
                </main>
            </div>

            @include('layouts.mobile-nav')
        </div>

        <!-- Toasts (Preline-style) -->
        <div id="gc-toasts" class="fixed inset-x-3 top-3 z-[100] space-y-3 sm:left-auto sm:right-4 sm:top-4"></div>
    </body>
</html>
