<?php

namespace App\Http\Controllers\Api\V1;

use App\Infrastructure\Db\SchemaIntrospector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ClientesController
{
    public function __construct(
        private readonly SchemaIntrospector $schema,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $table = (string) config('gc.tables.clientes', 'clientes');
        if (!$this->schema->hasTable($table)) {
            return response()->json([
                'ok' => false,
                'error' => "Table not found: {$table}",
            ], 501);
        }

        $limit = max(1, min(200, (int) $request->query('limit', 50)));
        $qText = trim((string) $request->query('q', ''));
        $cols = [
            'id',
            'telefono',
            'nombre',
            'modelos_cerraduras',
            'direccion',
            'canal',
            'notas',
            'primera_atencion',
            'ultima_atencion',
            'total_tickets',
            'fecha_instalacion',
            'garantia_meses',
            'tipo_cliente',
            'perfil_interaccion',
            'calificacion_promedio',
            'total_calificaciones',
            'email',
            'tipo_documento',
            'numero_documento',
            'razon_social',
            'created_at',
            'updated_at',
        ];
        $cols = $this->schema->existingColumns($table, $cols);
        if ($cols === []) {
            $cols = ['*'];
        }

        $qb = DB::table($table)->select($cols);

        if ($qText !== '' && $cols !== ['*']) {
            $terms = preg_split('/\s+/u', mb_strtolower($qText), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($terms as $term) {
                $like = '%' . $term . '%';
                $qb->where(function ($where) use ($cols, $like) {
                    foreach ($cols as $column) {
                        $where->orWhereRaw("lower(coalesce({$column}::text, '')) like ?", [$like]);
                    }
                });
            }

            $priorityColumns = array_values(array_intersect([
                'telefono',
                'numero_documento',
                'nombre',
                'razon_social',
                'email',
            ], $cols));
            if ($priorityColumns !== []) {
                $prefix = mb_strtolower($qText) . '%';
                $cases = [];
                $bindings = [];
                foreach ($priorityColumns as $index => $column) {
                    $cases[] = "when lower(coalesce({$column}::text, '')) like ? then {$index}";
                    $bindings[] = $prefix;
                }
                $qb->orderByRaw('case ' . implode(' ', $cases) . ' else ' . count($priorityColumns) . ' end', $bindings);
            }
        }

        $rows = $qb->orderBy('id', 'desc')->limit($limit)->get();

        return response()->json([
            'ok' => true,
            'data' => $rows,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $table = (string) config('gc.tables.clientes', 'clientes');
        if (!$this->schema->hasTable($table)) {
            return response()->json(['ok' => false, 'error' => "Table not found: {$table}"], 501);
        }

        $cliente = DB::table($table)->where('id', $id)->first();
        if (!$cliente) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        // Relacionados según spec
        $tickets = $this->schema->hasTable('tickets')
            ? DB::table('tickets')->where('cliente_wa', $cliente->telefono)->orderBy('id', 'desc')->limit(10)->get()
            : collect();

        $ventas = $this->schema->hasTable('ventas')
            ? DB::table('ventas')->where('cliente_id', $id)->orderBy('id', 'desc')->limit(10)->get()
            : collect();

        $dispositivos = $this->schema->hasTable('dispositivos_cliente')
            ? DB::table('dispositivos_cliente')->where('cliente_wa', $cliente->telefono)->orderBy('id', 'desc')->limit(10)->get()
            : collect();

        return response()->json([
            'ok' => true,
            'data' => [
                'cliente' => $cliente,
                'tickets' => $tickets,
                'ventas' => $ventas,
                'dispositivos' => $dispositivos,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $table = (string) config('gc.tables.clientes', 'clientes');
        if (!$this->schema->hasTable($table)) {
            return response()->json(['ok' => false, 'error' => "Table not found: {$table}"], 501);
        }

        $data = $request->validate([
            'telefono' => ['required', 'string', 'max:50'],
            'nombre' => ['nullable', 'string', 'max:200'],
            'modelos_cerraduras' => ['nullable', 'string'],
            'direccion' => ['nullable', 'string'],
            'canal' => ['nullable', 'string', 'max:50'],
            'notas' => ['nullable', 'string'],
            'fecha_instalacion' => ['nullable', 'date'],
            'garantia_meses' => ['nullable', 'integer', 'min:0'],
            'tipo_cliente' => ['nullable', 'string', 'max:50'],
            'perfil_interaccion' => ['nullable', 'string'],
            'email' => ['nullable', 'string', 'max:150'],
            'tipo_documento' => ['nullable', 'string', 'max:10'],
            'numero_documento' => ['nullable', 'string', 'max:20'],
            'razon_social' => ['nullable', 'string', 'max:200'],
        ]);

        $cols = $this->schema->existingColumns($table, array_keys($data));
        $insert = [];
        foreach ($cols as $c) {
            $insert[$c] = $data[$c] ?? null;
        }

        $id = DB::table($table)->insertGetId(array_merge($insert, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return response()->json(['ok' => true, 'data' => DB::table($table)->where('id', $id)->first()], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $table = (string) config('gc.tables.clientes', 'clientes');
        if (!$this->schema->hasTable($table)) {
            return response()->json(['ok' => false, 'error' => "Table not found: {$table}"], 501);
        }

        $cliente = DB::table($table)->where('id', $id)->first();
        if (!$cliente) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        $data = $request->validate([
            'telefono' => ['sometimes', 'string', 'max:50'],
            'nombre' => ['sometimes', 'nullable', 'string', 'max:200'],
            'modelos_cerraduras' => ['sometimes', 'nullable', 'string'],
            'direccion' => ['sometimes', 'nullable', 'string'],
            'canal' => ['sometimes', 'nullable', 'string', 'max:50'],
            'notas' => ['sometimes', 'nullable', 'string'],
            'fecha_instalacion' => ['sometimes', 'nullable', 'date'],
            'garantia_meses' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'tipo_cliente' => ['sometimes', 'nullable', 'string', 'max:50'],
            'perfil_interaccion' => ['sometimes', 'nullable', 'string'],
            'email' => ['sometimes', 'nullable', 'string', 'max:150'],
            'tipo_documento' => ['sometimes', 'nullable', 'string', 'max:10'],
            'numero_documento' => ['sometimes', 'nullable', 'string', 'max:20'],
            'razon_social' => ['sometimes', 'nullable', 'string', 'max:200'],
        ]);

        $cols = $this->schema->existingColumns($table, array_keys($data));
        $update = [];
        foreach ($cols as $c) {
            if (array_key_exists($c, $data)) {
                $update[$c] = $data[$c];
            }
        }

        DB::table($table)->where('id', $id)->update(array_merge($update, ['updated_at' => now()]));

        return response()->json(['ok' => true, 'data' => DB::table($table)->where('id', $id)->first()]);
    }

    public function destroy(int $id): JsonResponse
    {
        $table = (string) config('gc.tables.clientes', 'clientes');
        if (!$this->schema->hasTable($table)) {
            return response()->json(['ok' => false, 'error' => "Table not found: {$table}"], 501);
        }

        DB::table($table)->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }
}
