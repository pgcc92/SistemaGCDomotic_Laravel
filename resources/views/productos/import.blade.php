<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="text-lg font-semibold text-slate-900">Importar productos (CSV)</div>
            <a href="{{ route('productos.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50">Volver</a>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="max-w-2xl rounded-xl border border-slate-200 bg-white p-5 space-y-4">
        <div class="text-sm font-semibold text-slate-900">Formato esperado</div>
        <div class="text-sm text-slate-600">
            CSV con columnas: <code class="px-1 py-0.5 bg-slate-100 rounded">sku,nombre,precio</code>
        </div>

        <form method="POST" action="{{ route('productos.import') }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <input type="file" name="file" accept=".csv,text/csv,text/plain" class="w-full rounded-lg border-slate-300 bg-white focus:border-primary focus:ring-primary" required />
            <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                Importar
            </button>
        </form>
    </div>
</x-app-layout>

