<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="text-lg font-semibold text-slate-900">Movimiento de stock</div>
            <a href="{{ route('productos.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50">Volver</a>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('productos.movimiento') }}" class="max-w-2xl rounded-xl border border-slate-200 bg-white p-5 space-y-4">
        @csrf

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="text-xs text-slate-500">Producto ID</label>
                <input name="producto_id" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" required />
            </div>
            <div>
                <label class="text-xs text-slate-500">Sucursal ID (opcional)</label>
                <input name="sucursal_id" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="text-xs text-slate-500">Tipo</label>
                <select name="tipo" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" required>
                    <option value="ENTRADA">ENTRADA</option>
                    <option value="SALIDA">SALIDA</option>
                    <option value="VENTA">VENTA</option>
                    <option value="DEVOLUCION">DEVOLUCION</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-slate-500">Cantidad</label>
                <input name="cantidad" type="number" min="1" value="1" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" required />
            </div>
            <div>
                <label class="text-xs text-slate-500">Motivo (opcional)</label>
                <input name="motivo" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-2">
            <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                Registrar movimiento
            </button>
        </div>
    </form>
</x-app-layout>

