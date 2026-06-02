<x-app-layout>
    <x-slot name="header">
        <div class="text-lg font-semibold text-slate-900">{{ $cliente ? 'Editar cliente' : 'Crear cliente' }}</div>
    </x-slot>

    <div class="rounded-xl border border-slate-200 bg-white p-5 max-w-2xl">
        <form method="POST" action="{{ $cliente ? route('clientes.update', ['id' => $cliente->id]) : route('clientes.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700">Teléfono</label>
                <input name="telefono" value="{{ old('telefono', $cliente->telefono ?? '') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" required />
                @error('telefono') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Nombre</label>
                <input name="nombre" value="{{ old('nombre', $cliente->nombre ?? '') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
                @error('nombre') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input name="email" value="{{ old('email', $cliente->email ?? '') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
                @error('email') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Dirección</label>
                <textarea name="direccion" class="mt-1 w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" rows="3">{{ old('direccion', $cliente->direccion ?? '') }}</textarea>
                @error('direccion') <div class="mt-1 text-sm text-rose-600">{{ $message }}</div> @enderror
            </div>

            <div class="pt-2 flex items-center gap-2">
                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                    Guardar
                </button>
                <a href="{{ route('clientes.index') }}" class="text-sm text-slate-600 hover:underline">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>

