<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Rbac\RbacService;
use App\Infrastructure\Db\SchemaIntrospector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TicketsController
{
    public function __construct(
        private readonly SchemaIntrospector $schema,
        private readonly RbacService $rbac,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $table = (string) config('gc.tables.tickets', 'tickets');
        if (!$this->schema->hasTable($table)) {
            return response()->json(['ok' => false, 'error' => "Table not found: {$table}"], 501);
        }

        $clientesTable = (string) config('gc.tables.clientes', 'clientes');
        $hasClientes = $this->schema->hasTable($clientesTable);

        $uid = (int) $request->attributes->get('remote_uid', 0);
        $user = $uid > 0 ? DB::table('usuarios')->where('usuarios.id', $uid)->select(['usuarios.*'])->first() : null;
        $canViewAll = $uid > 0 ? $this->rbac->canViewAll($uid, 'tickets') : false;

        $limit = max(1, min(200, (int) $request->query('limit', 50)));
        $qText = trim((string) $request->query('q', ''));

        $q = DB::table($table)->orderBy("{$table}.id", 'desc');
        if ($hasClientes) {
            $q->leftJoin($clientesTable . ' as c', 'c.telefono', '=', "{$table}.cliente_wa");
            $q->addSelect([
                "{$table}.*",
                DB::raw("c.nombre as cliente_nombre"),
                DB::raw("c.razon_social as cliente_razon_social"),
            ]);
        } else {
            $q->select(["{$table}.*"]);
        }

        // Técnico no-admin: solo ve asignados a su tecnico_id
        if (!$canViewAll && $user && $user->tecnico_id) {
            $q->where('tecnico_asignado', (int) $user->tecnico_id);
        }

        if ($qText !== '') {
            $qq = '%' . mb_strtolower($qText) . '%';
            $q->where(function ($w) use ($qq, $hasClientes) {
                $w->orWhereRaw('lower(ticket_id::text) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(cliente_wa,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(estado,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(categoria,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(prioridad,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(resumen,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(asunto,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(modelo_cerradura,\'\')) like ?', [$qq]);
                if ($hasClientes) {
                    $w->orWhereRaw('lower(coalesce(c.nombre,\'\')) like ?', [$qq])
                        ->orWhereRaw('lower(coalesce(c.razon_social,\'\')) like ?', [$qq]);
                }
            });
        }

        $rows = $q->limit($limit)->get();

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function show(string $ticketId): JsonResponse
    {
        $table = (string) config('gc.tables.tickets', 'tickets');
        if (!$this->schema->hasTable($table)) {
            return response()->json(['ok' => false, 'error' => "Table not found: {$table}"], 501);
        }

        $uid = (int) request()?->attributes->get('remote_uid', 0);
        $user = $uid > 0 ? DB::table('usuarios')->where('usuarios.id', $uid)->select(['usuarios.*'])->first() : null;
        $canViewAll = $uid > 0 ? $this->rbac->canViewAll($uid, 'tickets') : false;

        $ticket = DB::table($table)->where('ticket_id', $ticketId)->first();
        if (!$ticket) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        if (!$canViewAll && $user && $user->tecnico_id) {
            if ((int) $ticket->tecnico_asignado !== (int) $user->tecnico_id) {
                return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
            }
        }

        // Técnicos activos:
        // - Disponibilidad operativa depende de usuarios.dashboard_activo (no tecnicos.activo).
        // - El ticket referencia tecnicos.id (tecnico_asignado), por eso devolvemos tecnicos.*.
        $tecnicos = collect();
        if ($this->schema->hasTable('tecnicos') && $this->schema->hasTable('usuarios')) {
            $tecnicos = DB::table('tecnicos as t')
                ->join('usuarios as u', 'u.tecnico_id', '=', 't.id')
                ->where('u.dashboard_activo', true)
                ->select(['t.id', 't.nombre', 't.telefono', 't.rol', 't.activo', 't.especialidad'])
                ->distinct()
                ->orderBy('t.nombre')
                ->get();
        }

        $cliente = $this->schema->hasTable('clientes')
            ? DB::table('clientes')->where('telefono', (string) $ticket->cliente_wa)->first()
            : null;

        $mensajes = $this->schema->hasTable('mensajes_buffer')
            ? DB::table('mensajes_buffer')->where('ticket_id', (string) $ticket->ticket_id)->orderBy('id', 'desc')->limit(50)->get()
            : collect();

        // Chat completo:
        // - Fuente de verdad: conversaciones (WhatsApp IN/OUT, inbound/outbound).
        // - Fallback: mensajes_buffer SOLO si no hay conversaciones.
        //
        // Nota: en entornos reales, mensajes_buffer suele contener un "buffer" parcial y puede duplicar texto
        // que ya existe en conversaciones (con timestamps distintos). Para evitar duplicados en UI,
        // NO mezclamos ambos cuando hay conversaciones.
        $chat = collect();
        $chatFromConv = collect();
        if ($this->schema->hasTable('conversaciones')) {
            $rows = DB::table('conversaciones')
                ->where('ticket_id', (string) $ticket->ticket_id)
                ->orderBy('created_at')
                ->limit(500)
                ->get();

            $chatFromConv = $rows->map(function ($r) {
                // En DB real puede venir: IN/OUT o inbound/outbound.
                $dirRaw = (string) ($r->direccion ?? '');
                $dir = strtoupper($dirRaw);
                $from = Str::startsWith($dir, 'OUT') ? 'tecnico' : 'cliente';
                return [
                    // Prefijamos para evitar colisiones con mensajes_buffer (ambos usan SERIAL)
                    'id' => 'c' . $r->id,
                    'from' => $from,
                    'direccion' => $dir ?: null,
                    'estado_msg' => $r->estado_msg ?? null,
                    'remitente_tipo' => $r->remitente_tipo ?? null,
                    'texto' => $r->texto ?? null,
                    'media_url' => $r->media_url ?? null,
                    'tipo_mensaje' => $r->tipo_mensaje ?? null,
                    'created_at' => $r->created_at ?? null,
                    'source' => 'conversaciones',
                ];
            });
        }

        // Si hay conversaciones, usamos SOLO conversaciones.
        // Si no hay conversaciones, usamos mensajes_buffer como fallback.
        $combined = $chatFromConv->count() ? $chatFromConv : $mensajes->map(function ($m) {
            return [
                'id' => 'b' . $m->id,
                'from' => 'cliente',
                'direccion' => 'IN',
                'estado_msg' => null,
                'remitente_tipo' => null,
                'texto' => $m->texto ?? null,
                'media_url' => $m->media_url ?? null,
                'tipo_mensaje' => $m->tipo_mensaje ?? null,
                'created_at' => $m->created_at ?? null,
                'source' => 'mensajes_buffer',
            ];
        });

        // Orden final por created_at (si falta fecha, cae al final)
        $chat = $combined->sortBy(function ($m) {
            $v = $m['created_at'] ?? null;
            try {
                return $v ? (string) $v : '9999-12-31 23:59:59';
            } catch (\Throwable) {
                return '9999-12-31 23:59:59';
            }
        })->values();

        return response()->json([
            'ok' => true,
            'data' => [
                'ticket' => $ticket,
                'tecnicos_activos' => $tecnicos,
                'cliente' => $cliente,
                'mensajes' => $mensajes,
                'chat' => $chat,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $table = (string) config('gc.tables.tickets', 'tickets');
        if (!$this->schema->hasTable($table)) {
            return response()->json(['ok' => false, 'error' => "Table not found: {$table}"], 501);
        }

        $payload = $request->validate([
            'ticket_id' => ['nullable', 'string', 'max:50'],
            'cliente_wa' => ['required', 'string', 'max:30'],
            'canal' => ['nullable', 'string', 'max:30'],
            'asunto' => ['nullable', 'string'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'prioridad' => ['nullable', 'string', 'max:20'],
            'estado' => ['nullable', 'string', 'max:30'],
            'tecnico_asignado' => ['nullable', 'integer'],
            'resumen' => ['nullable', 'string'],
            'modelo_cerradura' => ['nullable', 'string', 'max:120'],
            'categoria_problema' => ['nullable', 'string', 'max:100'],
        ]);

        $ticketId = $payload['ticket_id'] ?? ('T-' . now()->format('Ymd') . '-' . Str::upper(Str::random(8)));

        DB::table($table)->insert([
            'ticket_id' => $ticketId,
            'cliente_wa' => $payload['cliente_wa'],
            'canal' => $payload['canal'] ?? 'WHATSAPP',
            'asunto' => $payload['asunto'] ?? null,
            'categoria' => $payload['categoria'] ?? null,
            'prioridad' => $payload['prioridad'] ?? null,
            'estado' => $payload['estado'] ?? 'ABIERTO',
            'tecnico_asignado' => $payload['tecnico_asignado'] ?? null,
            'resumen' => $payload['resumen'] ?? null,
            'modelo_cerradura' => $payload['modelo_cerradura'] ?? null,
            'categoria_problema' => $payload['categoria_problema'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = DB::table($table)->where('ticket_id', $ticketId)->first();

        return response()->json(['ok' => true, 'data' => $row], 201);
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $table = (string) config('gc.tables.tickets', 'tickets');
        if (!$this->schema->hasTable($table)) {
            return response()->json(['ok' => false, 'error' => "Table not found: {$table}"], 501);
        }

        $payload = $request->validate([
            'estado' => ['nullable', 'string', 'max:30'],
            'tecnico_asignado' => ['nullable', 'integer'],
            'resumen' => ['nullable', 'string'],
            'asunto' => ['nullable', 'string'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'prioridad' => ['nullable', 'string', 'max:20'],
            'evidencia_url' => ['nullable', 'string'],
            'evidencia_recibida' => ['nullable', 'boolean'],
            'pendiente_evidencia' => ['nullable', 'boolean'],
            'video_enviado_id' => ['nullable', 'integer'],
            'venta_id' => ['nullable', 'integer'],
        ]);

        $row = DB::table($table)->where('ticket_id', $id)->first();
        if (!$row) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        $update = [];
        foreach ($payload as $k => $v) {
            if ($v !== null) {
                $update[$k] = $v;
            }
        }
        $update['updated_at'] = now();

        if ($update !== ['updated_at' => $update['updated_at']]) {
            DB::table($table)->where('ticket_id', $id)->update($update);
        }

        $fresh = DB::table($table)->where('ticket_id', $id)->first();
        return response()->json(['ok' => true, 'data' => $fresh]);
    }

    public function asignar(Request $request, string $ticketId): JsonResponse
    {
        $table = (string) config('gc.tables.tickets', 'tickets');
        if (!$this->schema->hasTable($table)) {
            return response()->json(['ok' => false, 'error' => "Table not found: {$table}"], 501);
        }

        $payload = $request->validate([
            'tecnico_id' => ['required', 'integer'],
            'comentario' => ['nullable', 'string'],
        ]);

        $ticket = DB::table($table)->where('ticket_id', $ticketId)->first();
        if (!$ticket) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        DB::transaction(function () use ($table, $ticketId, $payload) {
            DB::table($table)->where('ticket_id', $ticketId)->update([
                'tecnico_asignado' => (int) $payload['tecnico_id'],
                'updated_at' => now(),
            ]);

            if ($this->schema->hasTable('acciones_tecnicos')) {
                DB::table('acciones_tecnicos')->insert([
                    'ticket_id' => $ticketId,
                    'tecnico_id' => (int) $payload['tecnico_id'],
                    'accion' => 'ASIGNAR',
                    'comentario' => $payload['comentario'] ?? null,
                    'creado_en' => now(),
                ]);
            }

            DB::table('audit_log')->insert([
                'usuario_id' => (int) request()?->attributes->get('remote_uid', 0) ?: null,
                'accion' => 'ticket_assigned',
                'entidad' => 'tickets',
                'entidad_id' => $ticketId,
                'payload' => DB::raw("'{}'::jsonb"),
                'ip' => request()?->ip(),
                'created_at' => now(),
            ]);
        });

        return response()->json(['ok' => true]);
    }

    public function cerrar(Request $request, string $ticketId): JsonResponse
    {
        $table = (string) config('gc.tables.tickets', 'tickets');
        if (!$this->schema->hasTable($table)) {
            return response()->json(['ok' => false, 'error' => "Table not found: {$table}"], 501);
        }

        $ticket = DB::table($table)->where('ticket_id', $ticketId)->first();
        if (!$ticket) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        // Técnico dueño o admin (regla): si no admin y tiene tecnico_id debe ser el asignado
        $uid = (int) request()?->attributes->get('remote_uid', 0);
        $user = $uid > 0 ? DB::table('usuarios')->leftJoin('roles', 'roles.id', '=', 'usuarios.rol_id')->where('usuarios.id', $uid)->select(['usuarios.*', 'roles.codigo as rol_codigo'])->first() : null;
        $isAdmin = $user && (string) ($user->rol_codigo ?? '') === 'administrador';
        if (!$isAdmin && $user && $user->tecnico_id) {
            if ((int) $ticket->tecnico_asignado !== (int) $user->tecnico_id) {
                return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
            }
        }

        DB::table($table)->where('ticket_id', $ticketId)->update([
            'estado' => 'CERRADO',
            'updated_at' => now(),
        ]);

        DB::table('audit_log')->insert([
            'usuario_id' => $uid ?: null,
            'accion' => 'ticket_closed',
            'entidad' => 'tickets',
            'entidad_id' => $ticketId,
            'payload' => DB::raw("'{}'::jsonb"),
            'ip' => request()?->ip(),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
