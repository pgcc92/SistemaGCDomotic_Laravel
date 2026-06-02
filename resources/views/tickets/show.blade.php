<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-lg font-semibold text-slate-900">Ticket {{ $ticket['ticket_id'] ?? $ticketId }}</div>
                <div class="text-sm text-slate-500">
                    Estado: <span class="font-medium text-slate-700">{{ $ticket['estado'] ?? '—' }}</span>
                    @if(!empty($ticket['cliente_wa']))
                        · Cliente: <span class="font-medium text-slate-700">{{ $ticket['cliente_wa'] }}</span>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('tickets.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50">Volver</a>
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
            <div class="text-sm font-semibold text-slate-900">Detalle</div>
            <dl class="mt-3 space-y-2 text-sm">
                <div><dt class="text-slate-500">Asunto</dt><dd class="text-slate-900">{{ $ticket['asunto'] ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Categoría</dt><dd class="text-slate-900">{{ $ticket['categoria'] ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Prioridad</dt><dd class="text-slate-900">{{ $ticket['prioridad'] ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Técnico asignado</dt><dd class="text-slate-900">{{ $ticket['tecnico_asignado'] ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Creado</dt><dd class="text-slate-900">{{ $ticket['created_at'] ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Actualizado</dt><dd class="text-slate-900">{{ $ticket['updated_at'] ?? '—' }}</dd></div>
            </dl>

            <div class="mt-6 space-y-3">
                <form method="POST" action="{{ route('tickets.asignar', ['ticketId' => $ticketId]) }}" class="space-y-2">
                    @csrf
                    <div class="text-sm font-semibold text-slate-900">Asignar técnico</div>
                    <select name="tecnico_id" class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary">
                        <option value="">— Seleccionar —</option>
                        @foreach($tecnicos as $t)
                            @php($tid = (int) ($t->id ?? $t['id'] ?? 0))
                            <option value="{{ $tid }}">{{ $t->nombre ?? $t['nombre'] ?? ('Técnico '.$tid) }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="comentario" placeholder="Comentario (opcional)" class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary" />
                    <button class="w-full rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-white hover:bg-primary/90">Asignar</button>
                </form>

                <form method="POST" action="{{ route('tickets.cerrar', ['ticketId' => $ticketId]) }}">
                    @csrf
                    <button class="w-full rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Cerrar ticket</button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-semibold text-slate-900">Cliente</div>
                <pre class="mt-3 text-xs overflow-auto bg-slate-50 border border-slate-200 rounded-lg p-3">{{ json_encode($cliente, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="text-sm font-semibold text-slate-900">Mensajes (buffer)</div>
                <pre class="mt-3 text-xs overflow-auto bg-slate-50 border border-slate-200 rounded-lg p-3">{{ json_encode($mensajes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    </div>
</x-app-layout>

