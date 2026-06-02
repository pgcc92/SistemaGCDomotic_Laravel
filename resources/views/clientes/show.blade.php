<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-lg font-semibold text-slate-900">Cliente #{{ $cliente->id ?? '—' }}</div>
                <div class="text-sm text-slate-500">{{ $cliente->telefono ?? '' }} {{ $cliente->nombre ? '— '.$cliente->nombre : '' }}</div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('clientes.edit', ['id' => $cliente->id]) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50">Editar</a>
                <form method="POST" action="{{ route('clientes.destroy', ['id' => $cliente->id]) }}">
                    @csrf
                    <button class="rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700">Eliminar</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1 rounded-xl border border-slate-200 bg-white p-5">
            <div class="text-sm font-semibold text-slate-900">Ficha</div>
            <dl class="mt-3 space-y-2 text-sm">
                <div><dt class="text-slate-500">Nombre</dt><dd class="text-slate-900">{{ $cliente->nombre ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Teléfono</dt><dd class="text-slate-900">{{ $cliente->telefono ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Email</dt><dd class="text-slate-900">{{ $cliente->email ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Dirección</dt><dd class="text-slate-900">{{ $cliente->direccion ?? '—' }}</dd></div>
            </dl>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-semibold text-slate-900">Últimos tickets</div>
                <pre class="mt-3 text-xs overflow-auto bg-slate-50 border border-slate-200 rounded-lg p-3">{{ json_encode($tickets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-semibold text-slate-900">Últimas ventas</div>
                <pre class="mt-3 text-xs overflow-auto bg-slate-50 border border-slate-200 rounded-lg p-3">{{ json_encode($ventas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-semibold text-slate-900">Dispositivos</div>
                <pre class="mt-3 text-xs overflow-auto bg-slate-50 border border-slate-200 rounded-lg p-3">{{ json_encode($dispositivos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    </div>
</x-app-layout>

