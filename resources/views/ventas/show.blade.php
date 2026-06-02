<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-lg font-semibold text-slate-900">Venta #{{ $id }}</div>
                <div class="text-sm text-slate-500">
                    Estado: <span class="font-medium text-slate-700">{{ $venta['estado'] ?? '—' }}</span>
                    · Total: <span class="font-medium text-slate-700">{{ $venta['total'] ?? '—' }} {{ $venta['moneda'] ?? '' }}</span>
                </div>
            </div>
            <a href="{{ route('ventas.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50">Volver</a>
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

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <div class="text-sm font-semibold text-slate-900">Acciones</div>

            <form method="POST" action="{{ route('ventas.pagar', ['id' => $id]) }}" class="mt-4 space-y-2">
                @csrf
                <div class="text-sm font-semibold text-slate-900">Pagar</div>
                <input name="metodo" class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" placeholder="Método (opcional)" />
                <input name="referencia" class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" placeholder="Referencia (opcional)" />
                <button class="w-full rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-white hover:bg-primary/90">Marcar como pagada</button>
            </form>

            <form method="POST" action="{{ route('ventas.anular', ['id' => $id]) }}" class="mt-4 space-y-2">
                @csrf
                <div class="text-sm font-semibold text-slate-900">Anular</div>
                <input name="motivo" class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" placeholder="Motivo (opcional)" />
                <button class="w-full rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700">Anular venta</button>
            </form>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-semibold text-slate-900">Detalle</div>
                <pre class="mt-3 text-xs overflow-auto bg-slate-50 border border-slate-200 rounded-lg p-3">{{ json_encode($venta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    </div>
</x-app-layout>

