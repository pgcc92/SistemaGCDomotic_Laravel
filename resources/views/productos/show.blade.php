<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-lg font-semibold text-slate-900">Producto #{{ $id }}</div>
                <div class="text-sm text-slate-500">
                    {{ $producto['sku'] ?? '' }} {{ !empty($producto['nombre']) ? '— '.$producto['nombre'] : '' }}
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('productos.edit', ['id' => $id]) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50">Editar</a>
                <form method="POST" action="{{ route('productos.destroy', ['id' => $id]) }}">
                    @csrf
                    <button class="rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700">Eliminar</button>
                </form>
            </div>
        </div>
    </x-slot>

    @if($error)
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            {{ $error }}
        </div>
    @endif

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <div class="text-sm font-semibold text-slate-900">Ficha</div>
            <dl class="mt-3 space-y-2 text-sm">
                <div><dt class="text-slate-500">SKU</dt><dd class="text-slate-900">{{ $producto['sku'] ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Nombre</dt><dd class="text-slate-900">{{ $producto['nombre'] ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Precio</dt><dd class="text-slate-900">{{ $producto['precio'] ?? '—' }} {{ $producto['moneda'] ?? '' }}</dd></div>
                <div><dt class="text-slate-500">Costo</dt><dd class="text-slate-900">{{ $producto['costo'] ?? '—' }} {{ $producto['moneda'] ?? '' }}</dd></div>
                <div><dt class="text-slate-500">Activo</dt><dd class="text-slate-900">{{ array_key_exists('activo',$producto) ? ($producto['activo'] ? 'Sí' : 'No') : '—' }}</dd></div>
            </dl>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-semibold text-slate-900">Stock por sucursal</div>
                <pre class="mt-3 text-xs overflow-auto bg-slate-50 border border-slate-200 rounded-lg p-3">{{ json_encode($stock, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-semibold text-slate-900">Kardex</div>
                <pre class="mt-3 text-xs overflow-auto bg-slate-50 border border-slate-200 rounded-lg p-3">{{ json_encode($kardex, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    </div>
</x-app-layout>

