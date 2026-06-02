<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="text-lg font-semibold text-slate-900">Nueva venta</div>
            <a href="{{ route('ventas.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50">Volver</a>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('ventas.store') }}" class="grid gap-6 lg:grid-cols-3">
        @csrf

        <div class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-1 space-y-3">
            <div class="text-sm font-semibold text-slate-900">Datos</div>

            <div>
                <label class="text-xs text-slate-500">Cliente ID (opcional)</label>
                <input name="cliente_id" value="{{ old('cliente_id') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
            </div>

            <div>
                <label class="text-xs text-slate-500">Ticket ID (opcional)</label>
                <input name="ticket_id" value="{{ old('ticket_id') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
            </div>

            <div>
                <label class="text-xs text-slate-500">Sucursal ID (opcional)</label>
                <input name="sucursal_id" value="{{ old('sucursal_id') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
            </div>

            <div>
                <label class="text-xs text-slate-500">Tipo documento</label>
                <select name="tipo_documento" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary">
                    @php($tipo = old('tipo_documento', 'NOTA_VENTA'))
                    <option value="NOTA_VENTA" @selected($tipo === 'NOTA_VENTA')>NOTA_VENTA</option>
                    <option value="FACTURA" @selected($tipo === 'FACTURA')>FACTURA</option>
                    <option value="BOLETA" @selected($tipo === 'BOLETA')>BOLETA</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-slate-500">Serie (opcional)</label>
                    <input name="serie_documento" value="{{ old('serie_documento') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" placeholder="NV01/F001/B001" />
                </div>
                <div>
                    <label class="text-xs text-slate-500">Número (opcional)</label>
                    <input name="numero_documento" value="{{ old('numero_documento') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-slate-500">Moneda</label>
                    <select name="moneda" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary">
                        @php($mon = old('moneda', 'PEN'))
                        <option value="PEN" @selected($mon === 'PEN')>PEN</option>
                        <option value="USD" @selected($mon === 'USD')>USD</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-slate-500">Tipo cambio (opcional)</label>
                    <input name="tipo_cambio" value="{{ old('tipo_cambio') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
                </div>
            </div>

            <div>
                <label class="text-xs text-slate-500">Método pago (opcional)</label>
                <input name="metodo_pago" value="{{ old('metodo_pago') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
            </div>

            <div>
                <label class="text-xs text-slate-500">Estado inicial (opcional)</label>
                <select name="estado" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary">
                    @php($est = old('estado', 'PENDIENTE'))
                    <option value="PENDIENTE" @selected($est === 'PENDIENTE')>PENDIENTE</option>
                    <option value="PAGADA" @selected($est === 'PAGADA')>PAGADA</option>
                </select>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-2 space-y-3">
            <div class="text-sm font-semibold text-slate-900">Items</div>
            <div class="text-xs text-slate-500">
                Enviar un JSON array con items: ejemplo:
                <code class="px-1 py-0.5 bg-slate-100 rounded">[{ "producto_id": 1, "descripcion":"Item", "cantidad":1, "precio_unit": 10 }]</code>
            </div>

            <textarea name="items_json" rows="14" class="w-full rounded-lg border-slate-300 font-mono text-xs focus:border-primary focus:ring-primary">{{ old('items_json', \"[{\\n  \\\"producto_id\\\": null,\\n  \\\"descripcion\\\": \\\"Servicio\\\",\\n  \\\"cantidad\\\": 1,\\n  \\\"precio_unit\\\": 0,\\n  \\\"descuento\\\": 0\\n}]\") }}</textarea>

            <div class="flex items-center justify-end gap-2">
                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                    Crear venta
                </button>
            </div>
        </div>
    </form>
</x-app-layout>

