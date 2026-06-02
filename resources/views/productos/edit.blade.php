<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="text-lg font-semibold text-slate-900">Editar producto #{{ $id }}</div>
            <a href="{{ route('productos.show', ['id' => $id]) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50">Volver</a>
        </div>
    </x-slot>

    @if($error)
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            {{ $error }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('productos.update', ['id' => $id]) }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3">
        @csrf

        <div class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-1 space-y-3">
            <div class="text-sm font-semibold text-slate-900">Datos</div>

            <div>
                <label class="text-xs text-slate-500">SKU</label>
                <input name="sku" value="{{ old('sku', $producto['sku'] ?? '') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
            </div>

            <div>
                <label class="text-xs text-slate-500">Nombre</label>
                <input name="nombre" value="{{ old('nombre', $producto['nombre'] ?? '') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-slate-500">Precio</label>
                    <input name="precio" value="{{ old('precio', $producto['precio'] ?? 0) }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
                </div>
                <div>
                    <label class="text-xs text-slate-500">Costo</label>
                    <input name="costo" value="{{ old('costo', $producto['costo'] ?? 0) }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-slate-500">Moneda</label>
                    @php($mon = old('moneda', $producto['moneda'] ?? 'PEN'))
                    <select name="moneda" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary">
                        <option value="PEN" @selected($mon === 'PEN')>PEN</option>
                        <option value="USD" @selected($mon === 'USD')>USD</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-slate-500">Categoría</label>
                    <input name="categoria" value="{{ old('categoria', $producto['categoria'] ?? '') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
                </div>
            </div>

            <div>
                <label class="text-xs text-slate-500">Modelo</label>
                <input name="modelo" value="{{ old('modelo', $producto['modelo'] ?? '') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
            </div>

            <div>
                <label class="text-xs text-slate-500">Imagen URL</label>
                <input name="imagen_url" value="{{ old('imagen_url', $producto['imagen_url'] ?? '') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
            </div>

            <div>
                <label class="text-xs text-slate-500">Imagen archivo (opcional)</label>
                <input type="file" name="imagen_file" accept="image/*" class="mt-1 w-full rounded-lg border-slate-300 bg-white focus:border-primary focus:ring-primary" />
            </div>

            <div class="flex items-center gap-2">
                <input id="activo" type="checkbox" name="activo" value="1" class="rounded border-slate-300 text-primary focus:ring-primary"
                       @checked(old('activo', $producto['activo'] ?? true)) />
                <label for="activo" class="text-sm text-slate-700">Activo</label>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-2 space-y-3">
            <div class="text-sm font-semibold text-slate-900">Descripción</div>
            <textarea name="descripcion" rows="4" class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary">{{ old('descripcion', $producto['descripcion'] ?? '') }}</textarea>

            <div class="mt-2">
                <div class="text-sm font-semibold text-slate-900">Stock meta por sucursal (stock_min / ubicación)</div>
                <div class="text-xs text-slate-500">
                    JSON array: <code class="px-1 py-0.5 bg-slate-100 rounded">[{ "sucursal_id": 1, "stock_min": 2, "ubicacion": "A1" }]</code>
                </div>
                <textarea name="stock_meta_json" rows="8" class="mt-2 w-full rounded-lg border-slate-300 font-mono text-xs focus:border-primary focus:ring-primary">{{ old('stock_meta_json') }}</textarea>
                <div class="mt-2 text-xs text-slate-500">Stock actual por sucursal (solo lectura):</div>
                <pre class="mt-2 text-xs overflow-auto bg-slate-50 border border-slate-200 rounded-lg p-3">{{ json_encode($stock, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                    Guardar cambios
                </button>
            </div>
        </div>
    </form>
</x-app-layout>
