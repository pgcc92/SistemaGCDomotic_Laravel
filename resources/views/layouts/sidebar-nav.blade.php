@php
    $active = fn ($pattern) => request()->routeIs($pattern) ? 'bg-primary/10 text-primary ring-1 ring-primary/15' : 'text-slate-700 hover:bg-slate-100';
    $perms = app(\App\Infrastructure\Remote\RemoteRbacClient::class)->myPermissions();
    $can = function (string $modulo, string $accion = 'ver') use ($perms): bool {
        return (bool) (($perms['*']['*'] ?? false) || ($perms[$modulo][$accion] ?? false));
    };

    $showClientes = $can('clientes', 'ver');
    $showTickets = $can('tickets', 'ver');
    $showAgenda = $can('agenda', 'ver');
    $showVentas = $can('ventas', 'ver');
    $showComisiones = $can('comisiones', 'ver');
    $showReportes = $can('reportes', 'ver');

    $showProductos = $can('productos', 'ver') || $can('stock', 'ver');
    $showDispositivos = $can('dispositivos', 'ver');
    $showSoporteVideos = $can('soporte_videos', 'ver');

    $showUsuarios = $can('usuarios', 'ver');
    $showRoles = $can('roles', 'ver');
    $showPermisos = $can('permisos', 'ver');
    $showSucursales = $can('sucursales', 'ver');
    $showAuditoria = $can('auditoria', 'ver');
    $showConfiguracion = $can('configuracion', 'ver');

    $anyOps = $showClientes || $showTickets || $showAgenda || $showVentas || $showComisiones || $showReportes;
    $anyInv = $showProductos || $showDispositivos || $showSoporteVideos;
    $anyAdmin = $showUsuarios || $showRoles || $showPermisos || $showSucursales || $showAuditoria || $showConfiguracion;

    $openOps = $anyOps && (request()->routeIs('clientes.*') || request()->routeIs('tickets.*') || request()->routeIs('agenda.*') || request()->routeIs('ventas.*') || request()->routeIs('comisiones.*') || request()->routeIs('reportes.*'));
    $openInv = $anyInv && (request()->routeIs('productos.*') || request()->routeIs('dispositivos.*') || request()->routeIs('soporte-videos.*'));
    $openAdmin = $anyAdmin && (request()->routeIs('usuarios.*') || request()->routeIs('roles.*') || request()->routeIs('permisos.*') || request()->routeIs('sucursales.*') || request()->routeIs('auditoria.*') || request()->routeIs('configuracion.*'));
@endphp

<nav class="mt-6 space-y-3" x-data="{ ops: @js($openOps), inv: @js($openInv), admin: @js($openAdmin) }">
    @if($can('dashboard','ver'))
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium {{ $active('dashboard') }}">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6" />
            </svg>
            <span>Dashboard</span>
        </a>
    @endif

    @if($anyOps)
    <div class="rounded-2xl border border-slate-200 bg-white p-2">
        <button type="button" class="w-full flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                @click="ops = !ops">
            <span class="uppercase tracking-wide">Operación</span>
            <svg class="h-4 w-4 text-slate-400 transition" :class="ops ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        <div x-show="ops" x-collapse>
            @if($showClientes)
                <a href="{{ route('clientes.index') }}" class="mt-1 flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ $active('clientes.*') }}">
                    <span>Clientes</span>
                </a>
            @endif
            @if($showTickets)
                <a href="{{ route('tickets.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ $active('tickets.*') }}">
                    <span>Tickets</span>
                </a>
            @endif
            @if($showAgenda)
                <a href="{{ route('agenda.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ $active('agenda.*') }}">
                    <span>Agenda</span>
                </a>
            @endif
            @if($showVentas)
                <a href="{{ route('ventas.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ $active('ventas.*') }}">
                    <span>Ventas</span>
                </a>
            @endif
            @if($showComisiones)
                <a href="{{ route('comisiones.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ $active('comisiones.*') }}">
                    <span>Comisiones</span>
                </a>
            @endif
            @if($showReportes)
                <a href="{{ route('reportes.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ $active('reportes.*') }}">
                    <span>Reportes</span>
                </a>
            @endif
        </div>
    </div>
    @endif

    @if($anyInv)
    <div class="rounded-2xl border border-slate-200 bg-white p-2">
        <button type="button" class="w-full flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                @click="inv = !inv">
            <span class="uppercase tracking-wide">Inventario</span>
            <svg class="h-4 w-4 text-slate-400 transition" :class="inv ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        <div x-show="inv" x-collapse>
            @if($showProductos)
                <a href="{{ route('productos.index') }}" class="mt-1 flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ $active('productos.*') }}">
                    <span>Productos / Stock</span>
                </a>
            @endif
            @if($showDispositivos)
                <a href="{{ route('dispositivos.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ $active('dispositivos.*') }}">
                    <span>Instalaciones</span>
                </a>
            @endif
            @if($showSoporteVideos)
                <a href="{{ route('soporte-videos.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ $active('soporte-videos.*') }}">
                    <span>Soporte videos</span>
                </a>
            @endif
        </div>
    </div>
    @endif

    @if($anyAdmin)
    <div class="rounded-2xl border border-slate-200 bg-white p-2">
        <button type="button" class="w-full flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                @click="admin = !admin">
            <span class="uppercase tracking-wide">Administración</span>
            <svg class="h-4 w-4 text-slate-400 transition" :class="admin ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        <div x-show="admin" x-collapse>
            @if($showUsuarios)
                <a href="{{ route('usuarios.index') }}" class="mt-1 flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ $active('usuarios.*') }}">
                    <span>Usuarios</span>
                </a>
            @endif
            @if($showRoles)
                <a href="{{ route('roles.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ $active('roles.*') }}">
                    <span>Roles</span>
                </a>
            @endif
            @if($showPermisos)
                <a href="{{ route('permisos.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ $active('permisos.*') }}">
                    <span>Permisos</span>
                </a>
            @endif
            @if($showSucursales)
                <a href="{{ route('sucursales.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ $active('sucursales.*') }}">
                    <span>Sucursales</span>
                </a>
            @endif
            @if($showAuditoria)
                <a href="{{ route('auditoria.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ $active('auditoria.*') }}">
                    <span>Auditoría</span>
                </a>
            @endif
            @if($showConfiguracion)
                <a href="{{ route('configuracion.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm {{ $active('configuracion.*') }}">
                    <span>Configuración</span>
                </a>
            @endif
        </div>
    </div>
    @endif
</nav>
