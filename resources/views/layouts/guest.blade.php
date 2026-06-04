<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="app-version" content="{{ app(\App\Support\AppVersion::class)->current() }}">

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
    <body class="antialiased text-slate-100" style="font-family: var(--gc-font), ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;">
        <div class="min-h-screen relative overflow-hidden">
            <div class="absolute inset-0"
                 style="background:
                        radial-gradient(1200px circle at 15% 10%, rgb(var(--gc-login-from) / 0.55) 0%, rgba(0,0,0,0) 45%),
                        radial-gradient(1000px circle at 85% 85%, rgb(var(--gc-login-to) / 0.55) 0%, rgba(0,0,0,0) 50%),
                        linear-gradient(135deg, rgb(var(--gc-login-from) / 1) 0%, rgb(var(--gc-login-to) / 1) 100%);">
            </div>
            <div class="absolute inset-0 opacity-30" style="background-image: radial-gradient(rgba(255,255,255,0.08) 1px, transparent 1px); background-size: 14px 14px;"></div>

            <div class="relative z-10 mx-auto flex min-h-screen max-w-6xl items-center justify-center px-4 py-10">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
