@php
    $mobilePerms = app(\App\Infrastructure\Remote\RemoteRbacClient::class)->myPermissions();
    $mobileCan = function (string $modulo, string $accion = 'ver') use ($mobilePerms): bool {
        return (bool) (($mobilePerms['*']['*'] ?? false) || ($mobilePerms[$modulo][$accion] ?? false));
    };
    $mobileItems = array_values(array_filter([
        ['label' => 'Inicio', 'route' => 'dashboard', 'active' => 'dashboard', 'show' => $mobileCan('dashboard')],
        ['label' => 'Tickets', 'route' => 'tickets.index', 'active' => 'tickets.*', 'show' => $mobileCan('tickets')],
        ['label' => 'Agenda', 'route' => 'agenda.index', 'active' => 'agenda.*', 'show' => $mobileCan('agenda')],
        ['label' => 'Ventas', 'route' => 'ventas.index', 'active' => 'ventas.*', 'show' => $mobileCan('ventas')],
        ['label' => 'Stock', 'route' => 'productos.index', 'active' => 'productos.*', 'show' => $mobileCan('productos') || $mobileCan('stock')],
    ], fn (array $item): bool => $item['show']));
@endphp

@if(count($mobileItems))
    <nav class="gc-safe-bottom fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 shadow-[0_-8px_30px_rgba(15,23,42,0.08)] backdrop-blur lg:hidden"
         aria-label="Navegación rápida">
        <div class="mx-auto flex max-w-lg items-stretch px-2 pt-2">
            @foreach($mobileItems as $item)
                @php($isMobileActive = request()->routeIs($item['active']))
                <a href="{{ route($item['route']) }}"
                   class="flex min-w-0 flex-1 flex-col items-center justify-center gap-1 rounded-xl px-1 py-2 text-[11px] font-semibold {{ $isMobileActive ? 'bg-primary/10 text-primary' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $isMobileActive ? 'bg-primary' : 'bg-slate-300' }}"></span>
                    <span class="truncate">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>
@endif
