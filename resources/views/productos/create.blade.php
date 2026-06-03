<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="text-lg font-semibold text-slate-900">Nuevo producto</div>
            <a href="{{ route('productos.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50">Volver</a>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('productos.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3">
        @csrf

        <div class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-1 space-y-3">
            <div class="text-sm font-semibold text-slate-900">Datos</div>

            <div>
                <label class="text-xs text-slate-500">SKU</label>
                <input name="sku" value="{{ old('sku') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" required />
            </div>

            <div>
                <label class="text-xs text-slate-500">Nombre</label>
                <input name="nombre" value="{{ old('nombre') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" required />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-slate-500">Precio</label>
                    <input name="precio" value="{{ old('precio', 0) }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
                </div>
                <div>
                    <label class="text-xs text-slate-500">Costo</label>
                    <input name="costo" value="{{ old('costo', 0) }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
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
                    <label class="text-xs text-slate-500">Categoría</label>
                    <input name="categoria" value="{{ old('categoria') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
                </div>
            </div>

            <div>
                <label class="text-xs text-slate-500">Modelo</label>
                <input name="modelo" value="{{ old('modelo') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
            </div>

            <div>
                <label class="text-xs text-slate-500">Imagen URL (opcional)</label>
                <input name="imagen_url" value="{{ old('imagen_url') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
            </div>

            <div>
                <label class="text-xs text-slate-500">Imagen archivo (opcional)</label>
                <input type="file" name="imagen_file" accept="image/*" class="mt-1 w-full rounded-lg border-slate-300 bg-white focus:border-primary focus:ring-primary" />
                <div class="mt-1 text-xs text-slate-500">Si subes archivo, se guarda en el almacenamiento persistente de imágenes y se envía su URL a Postgres.</div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-2 space-y-3">
            <div class="text-sm font-semibold text-slate-900">Detalles</div>

            <div>
                <label class="text-xs text-slate-500">Descripción (opcional)</label>
                <textarea name="descripcion" rows="4" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary">{{ old('descripcion') }}</textarea>
            </div>

            <div>
                <div class="text-sm font-semibold text-slate-900">Stock inicial (opcional)</div>
                <div class="text-xs text-slate-500">
                    JSON array: <code class="px-1 py-0.5 bg-slate-100 rounded">[{ "sucursal_id": 1, "cantidad": 10, "motivo":"Inicial" }]</code>
                </div>
                <textarea name="stock_inicial_json" rows="6" class="mt-2 w-full rounded-lg border-slate-300 font-mono text-xs focus:border-primary focus:ring-primary">{{ old('stock_inicial_json') }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                    Crear producto
                </button>
            </div>
        </div>
    </form>
</x-app-layout>
