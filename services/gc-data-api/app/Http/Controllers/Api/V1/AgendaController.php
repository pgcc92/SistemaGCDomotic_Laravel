<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Rbac\RbacService;
use App\Infrastructure\Db\SchemaIntrospector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AgendaController
{
    public function __construct(
        private readonly SchemaIntrospector $schema,
        private readonly RbacService $rbac,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $table = (string) config('gc.tables.agenda_instalaciones', 'agenda_instalaciones');
        if (!$this->schema->hasTable($table)) {
            return response()->json([
                'ok' => false,
                'error' => "La tabla '{$table}' no existe en Postgres. Crea la tabla agenda (agenda_instalaciones) y vuelve a intentar.",
            ], 501);
        }

        $limit = max(1, min(200, (int) $request->query('limit', 100)));

        $from = $request->query('from');
        $to = $request->query('to');
        $estado = $request->query('estado');
        $tecnicoId = $request->query('tecnico_id');
        $qText = trim((string) $request->query('q', ''));

        $q = DB::table($table)->orderBy('fecha_programada', 'asc')->orderBy('id', 'asc');

        // Visibilidad: admin ve todo; el resto solo lo asignado a su usuario (tecnico_id = uid)
        $uid = (int) $request->attributes->get('remote_uid', 0);
        $canViewAll = $uid > 0 ? $this->rbac->canViewAll($uid, 'agenda') : false;

        // Override explícito (UI): all=1 fuerza visibilidad general SOLO si el usuario tiene el permiso.
        // Esto protege contra escenarios donde el cliente desea "ver todo" pero el filtro por defecto
        // podría restringir (por ejemplo, por parámetros persistidos o compatibilidad de acciones).
        $explicitAll = (bool) $request->boolean('all', false);
        if ($explicitAll && $uid > 0) {
            $explicitAll = $this->rbac->isAdmin($uid) || $this->rbac->can($uid, 'agenda', 'ver_general');
        } else {
            $explicitAll = false;
        }

        if (!($canViewAll || $explicitAll) && $uid > 0) {
            $q->where('tecnico_id', $uid);
            // No permitimos filtrar por otro técnico si no es admin
            $tecnicoId = $uid;
        }

        if (is_string($from) && $from !== '') {
            $q->where('fecha_programada', '>=', $from);
        }
        if (is_string($to) && $to !== '') {
            $q->where('fecha_programada', '<=', $to);
        }
        if (is_string($estado) && $estado !== '') {
            $q->where('estado', strtoupper($estado));
        }
        if ($tecnicoId !== null && $tecnicoId !== '') {
            $q->where('tecnico_id', (int) $tecnicoId);
        }
        if ($qText !== '') {
            $qq = '%' . mb_strtolower($qText) . '%';
            $q->where(function ($w) use ($qq) {
                $w->orWhereRaw('lower(coalesce(cliente_wa,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(titulo,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(descripcion,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(tipo,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(estado,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(ticket_id::text,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(venta_id::text,\'\')) like ?', [$qq]);
            });
        }

        $rows = $q->limit($limit)->get();

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function show(int $id): JsonResponse
    {
        $table = (string) config('gc.tables.agenda_instalaciones', 'agenda_instalaciones');
        if (!$this->schema->hasTable($table)) {
            return response()->json([
                'ok' => false,
                'error' => "La tabla '{$table}' no existe en Postgres. Crea la tabla agenda (agenda_instalaciones) y vuelve a intentar.",
            ], 501);
        }

        $row = DB::table($table)->where('id', $id)->first();
        if (!$row) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        $uid = (int) request()->attributes->get('remote_uid', 0);
        $canViewAll = $uid > 0 ? $this->rbac->canViewAll($uid, 'agenda') : false;
        if (!$canViewAll && $uid > 0) {
            if ((int) ($row->tecnico_id ?? 0) !== $uid) {
                return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
            }
        }

        return response()->json(['ok' => true, 'data' => $row]);
    }

    public function store(Request $request): JsonResponse
    {
        $table = (string) config('gc.tables.agenda_instalaciones', 'agenda_instalaciones');
        if (!$this->schema->hasTable($table)) {
            return response()->json([
                'ok' => false,
                'error' => "La tabla '{$table}' no existe en Postgres. Crea la tabla agenda (agenda_instalaciones) y vuelve a intentar.",
            ], 501);
        }

        $uid = (int) $request->attributes->get('remote_uid', 0);
        if ($uid <= 0) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
        }
        $canViewAll = $this->rbac->canViewAll($uid, 'agenda');

        $p = $request->validate([
            'tipo' => ['required', 'string', 'max:20'],
            'estado' => ['required', 'string', 'max:20'],
            'venta_id' => ['nullable', 'integer'],
            'ticket_id' => ['nullable', 'string', 'max:50'],
            'cliente_id' => ['nullable', 'integer'],
            'cliente_wa' => ['nullable', 'string', 'max:30'],
            'tecnico_id' => ['nullable', 'integer'],
            'sucursal_id' => ['nullable', 'integer'],
            'titulo' => ['nullable', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'fecha_programada' => ['required', 'date'],
            'duracion_min' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'prioridad' => ['nullable', 'string', 'max:20'],
            'notas' => ['nullable', 'string'],
        ]);

        // Alcance parcial: siempre se asigna a sí mismo
        if (!$canViewAll) {
            $p['tecnico_id'] = $uid;
        }

        $id = DB::table($table)->insertGetId([
            'tipo' => strtoupper((string) $p['tipo']),
            'estado' => strtoupper((string) $p['estado']),
            'venta_id' => $p['venta_id'] ?? null,
            'ticket_id' => $p['ticket_id'] ?? null,
            'cliente_id' => $p['cliente_id'] ?? null,
            'cliente_wa' => $p['cliente_wa'] ?? null,
            'tecnico_id' => $p['tecnico_id'] ?? null,
            'sucursal_id' => $p['sucursal_id'] ?? null,
            'titulo' => $p['titulo'] ?? null,
            'descripcion' => $p['descripcion'] ?? null,
            'fecha_programada' => $p['fecha_programada'],
            'duracion_min' => $p['duracion_min'] ?? 60,
            'prioridad' => $p['prioridad'] ?? null,
            'notas' => $p['notas'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true, 'data' => DB::table($table)->where('id', $id)->first()], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $table = (string) config('gc.tables.agenda_instalaciones', 'agenda_instalaciones');
        if (!$this->schema->hasTable($table)) {
            return response()->json([
                'ok' => false,
                'error' => "La tabla '{$table}' no existe en Postgres. Crea la tabla agenda (agenda_instalaciones) y vuelve a intentar.",
            ], 501);
        }

        $row = DB::table($table)->where('id', $id)->first();
        if (!$row) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        $uid = (int) $request->attributes->get('remote_uid', 0);
        if ($uid <= 0) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
        }
        $canViewAll = $this->rbac->canViewAll($uid, 'agenda');
        if (!$canViewAll) {
            if ((int) ($row->tecnico_id ?? 0) !== $uid) {
                return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
            }
        }

        $p = $request->validate([
            'tipo' => ['nullable', 'string', 'max:20'],
            'estado' => ['nullable', 'string', 'max:20'],
            'venta_id' => ['nullable', 'integer'],
            'ticket_id' => ['nullable', 'string', 'max:50'],
            'cliente_id' => ['nullable', 'integer'],
            'cliente_wa' => ['nullable', 'string', 'max:30'],
            'tecnico_id' => ['nullable', 'integer'],
            'sucursal_id' => ['nullable', 'integer'],
            'titulo' => ['nullable', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'fecha_programada' => ['nullable', 'date'],
            'duracion_min' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'prioridad' => ['nullable', 'string', 'max:20'],
            'notas' => ['nullable', 'string'],
            // opcionales (si existen columnas en la tabla)
            'terminado_at' => ['nullable', 'date'],
            'evidencia_dispositivo_id' => ['nullable', 'integer'],
        ]);

        $extraCols = $this->schema->existingColumns($table, ['terminado_at', 'evidencia_dispositivo_id']);
        $hasTerminado = in_array('terminado_at', $extraCols, true);
        $hasEvidencia = in_array('evidencia_dispositivo_id', $extraCols, true);

        $upd = [];
        foreach ($p as $k => $v) {
            if ($v === null) continue;
            if ($k === 'tipo' || $k === 'estado') {
                $upd[$k] = strtoupper((string) $v);
            } else {
                $upd[$k] = $v;
            }
        }

        if (!$hasTerminado) {
            unset($upd['terminado_at']);
        }
        if (!$hasEvidencia) {
            unset($upd['evidencia_dispositivo_id']);
        }

        // Alcance parcial: no se permite reasignar a otro técnico
        if (!$canViewAll) {
            unset($upd['tecnico_id']);
        }
        $upd['updated_at'] = now();

        DB::table($table)->where('id', $id)->update($upd);

        return response()->json(['ok' => true, 'data' => DB::table($table)->where('id', $id)->first()]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $table = (string) config('gc.tables.agenda_instalaciones', 'agenda_instalaciones');
        if (!$this->schema->hasTable($table)) {
            return response()->json([
                'ok' => false,
                'error' => "La tabla '{$table}' no existe en Postgres. Crea la tabla agenda (agenda_instalaciones) y vuelve a intentar.",
            ], 501);
        }

        $row = DB::table($table)->where('id', $id)->first();
        if (!$row) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        $uid = (int) $request->attributes->get('remote_uid', 0);
        $isAdmin = $this->isAdminForRequest($request);
        if ($uid <= 0) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
        }
        if (!$isAdmin) {
            if ((int) ($row->tecnico_id ?? 0) !== $uid) {
                return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
            }
        }

        DB::table($table)->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }
}
