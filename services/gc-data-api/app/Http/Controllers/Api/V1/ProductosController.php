<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Rbac\RbacService;
use App\Domain\Stock\StockService;
use App\Infrastructure\Db\SchemaIntrospector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ProductosController
{
    public function __construct(
        private readonly SchemaIntrospector $schema,
        private readonly StockService $stock,
        private readonly RbacService $rbac,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        if ($resp = $this->authorize($request, 'productos', 'ver')) {
            return $resp;
        }

        $productos = (string) config('gc.tables.productos', 'productos');
        if (!$this->schema->hasTable($productos)) {
            return response()->json(['ok' => false, 'error' => "Table not found: {$productos}"], 501);
        }

        $limit = max(1, min(500, (int) $request->query('limit', 50)));
        $qText = trim((string) $request->query('q', ''));

        $qb = DB::table($productos);
        if ($qText !== '') {
            $qq = '%' . mb_strtolower($qText) . '%';
            $qb->where(function ($w) use ($qq) {
                $w->orWhereRaw('lower(coalesce(sku,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(nombre,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(modelo,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(categoria,\'\')) like ?', [$qq]);
            });
        }

        $rows = $qb->orderBy('id', 'desc')->limit($limit)->get();

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function show(int $id): JsonResponse
    {
        if ($resp = $this->authorize(request(), 'productos', 'ver')) {
            return $resp;
        }

        $productos = (string) config('gc.tables.productos', 'productos');
        $stockSucursal = (string) config('gc.tables.stock_sucursal', 'stock_sucursal');
        $movs = (string) config('gc.tables.movimientos_stock', 'movimientos_stock');
        $sucursales = (string) config('gc.tables.sucursales', 'sucursales');

        if (!$this->schema->hasTable($productos)) {
            return response()->json(['ok' => false, 'error' => "Table not found: {$productos}"], 501);
        }

        $p = DB::table($productos)->where('id', $id)->first();
        if (!$p) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        $stock = $this->schema->hasTable($stockSucursal)
            ? DB::table("{$stockSucursal} as ss")
                ->leftJoin("{$sucursales} as s", 's.id', '=', 'ss.sucursal_id')
                ->where('ss.producto_id', $id)
                ->orderBy('ss.sucursal_id')
                ->select(['ss.*', 's.nombre as sucursal_nombre', 's.codigo as sucursal_codigo'])
                ->get()
            : collect();

        $kardex = $this->schema->hasTable($movs)
            ? DB::table("{$movs} as m")
                ->leftJoin("{$sucursales} as so", 'so.id', '=', 'm.sucursal_origen')
                ->leftJoin("{$sucursales} as sd", 'sd.id', '=', 'm.sucursal_destino')
                ->where('m.producto_id', $id)
                ->orderBy('m.id', 'desc')
                ->limit(200)
                ->select([
                    'm.*',
                    'so.nombre as sucursal_origen_nombre',
                    'sd.nombre as sucursal_destino_nombre',
                ])
                ->get()
            : collect();

        return response()->json([
            'ok' => true,
            'data' => [
                'producto' => $p,
                'stock' => $stock,
                'kardex' => $kardex,
            ],
        ]);
    }

    public function stockData(Request $request): JsonResponse
    {
        if ($resp = $this->authorize($request, 'productos', 'ver')) {
            return $resp;
        }

        $productos = (string) config('gc.tables.productos', 'productos');
        $stockSucursal = (string) config('gc.tables.stock_sucursal', 'stock_sucursal');
        $sucursales = (string) config('gc.tables.sucursales', 'sucursales');

        if (!$this->schema->hasTable($productos) || !$this->schema->hasTable($stockSucursal) || !$this->schema->hasTable($sucursales)) {
            return response()->json(['ok' => false, 'error' => 'Tables not found'], 501);
        }

        $limit = max(1, min(5000, (int) $request->query('limit', 500)));

        $idsRaw = (string) $request->query('producto_ids', '');
        $ids = [];
        if ($idsRaw !== '') {
            foreach (preg_split('/\\s*,\\s*/', $idsRaw) ?: [] as $part) {
                $n = (int) $part;
                if ($n > 0) $ids[] = $n;
            }
            $ids = array_values(array_unique($ids));
            // Evitar queries enormes por URL.
            if (count($ids) > 800) {
                $ids = array_slice($ids, 0, 800);
            }
        }

        $rows = DB::table("{$stockSucursal} as ss")
            ->join("{$productos} as p", 'p.id', '=', 'ss.producto_id')
            ->join("{$sucursales} as s", 's.id', '=', 'ss.sucursal_id')
            ->when(count($ids) > 0, fn ($q) => $q->whereIn('ss.producto_id', $ids))
            ->orderBy('ss.producto_id')
            ->orderBy('ss.sucursal_id')
            ->limit($limit) // aplica solo si no se filtra por ids o si ids es muy grande
            ->select([
                'ss.id',
                'ss.producto_id',
                'p.sku',
                'p.nombre as producto_nombre',
                'ss.sucursal_id',
                's.codigo as sucursal_codigo',
                's.nombre as sucursal_nombre',
                'ss.stock',
                'ss.stock_min',
                'ss.ubicacion',
                'ss.updated_at',
            ])
            ->get();

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function kardexData(Request $request): JsonResponse
    {
        if ($resp = $this->authorize($request, 'productos', 'ver')) {
            return $resp;
        }

        $movs = (string) config('gc.tables.movimientos_stock', 'movimientos_stock');
        $productos = (string) config('gc.tables.productos', 'productos');
        $sucursales = (string) config('gc.tables.sucursales', 'sucursales');

        if (!$this->schema->hasTable($movs) || !$this->schema->hasTable($productos) || !$this->schema->hasTable($sucursales)) {
            return response()->json(['ok' => false, 'error' => 'Tables not found'], 501);
        }

        $limit = max(1, min(5000, (int) $request->query('limit', 500)));

        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        // Default: periodo actual (mes).
        if ($from === '' && $to === '') {
            $from = now()->startOfMonth()->toDateString();
            $to = now()->endOfMonth()->toDateString();
        }

        $applyRange = preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $from) && preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $to);

        $q = DB::table("{$movs} as m")
            ->join("{$productos} as p", 'p.id', '=', 'm.producto_id')
            ->leftJoin("{$sucursales} as so", 'so.id', '=', 'm.sucursal_origen')
            ->leftJoin("{$sucursales} as sd", 'sd.id', '=', 'm.sucursal_destino')
            ->when($applyRange, function ($qq) use ($from, $to) {
                // created_at puede ser timestamp: tomamos rango inclusivo por día
                return $qq->whereBetween('m.created_at', ["{$from} 00:00:00", "{$to} 23:59:59"]);
            })
            ->orderBy('m.id', 'desc')
            ->limit($limit)
            ->select([
                'm.id',
                'm.created_at',
                'm.tipo',
                'm.cantidad',
                'm.motivo',
                'm.venta_id',
                'm.usuario_id',
                'p.id as producto_id',
                'p.sku',
                'p.nombre as producto_nombre',
                'so.nombre as sucursal_origen',
                'sd.nombre as sucursal_destino',
            ])
            ->get();

        return response()->json(['ok' => true, 'data' => $q]);
    }

    public function movimiento(Request $request): JsonResponse
    {
        if ($resp = $this->authorize($request, 'productos', 'editar')) {
            return $resp;
        }

        $payload = $request->validate([
            'producto_id' => ['required', 'integer', 'min:1'],
            'sucursal_id' => ['nullable', 'integer', 'min:1'], // compat
            'sucursal_origen' => ['nullable', 'integer', 'min:1'],
            'sucursal_destino' => ['nullable', 'integer', 'min:1'],
            'tipo' => ['required', 'string', 'max:20'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'motivo' => ['nullable', 'string', 'max:150'],
        ]);

        $tipo = Str::upper((string) $payload['tipo']);
        if ($tipo === 'TRANSFER') {
            $o = (int) ($payload['sucursal_origen'] ?? 0);
            $d = (int) ($payload['sucursal_destino'] ?? 0);
            if ($o > 0 && $d > 0 && $o === $d) {
                return response()->json(['ok' => false, 'error' => 'La sucursal origen y destino no pueden ser la misma.'], 422);
            }
        }

        $uid = (int) $request->attributes->get('remote_uid', 0);

        DB::transaction(function () use ($payload, $uid) {
            $this->stock->movimiento([
                'producto_id' => (int) $payload['producto_id'],
                'sucursal_id' => $payload['sucursal_id'] ?? null,
                'sucursal_origen' => $payload['sucursal_origen'] ?? null,
                'sucursal_destino' => $payload['sucursal_destino'] ?? null,
                'tipo' => Str::upper((string) $payload['tipo']),
                'cantidad' => (int) $payload['cantidad'],
                'motivo' => $payload['motivo'] ?? null,
                'usuario_id' => $uid ?: null,
            ]);

            DB::table('audit_log')->insert([
                'usuario_id' => $uid ?: null,
                'accion' => 'stock_movement',
                'entidad' => 'productos',
                'entidad_id' => (string) $payload['producto_id'],
                'payload' => DB::raw("'{}'::jsonb"),
                'ip' => request()?->ip(),
                'created_at' => now(),
            ]);
        });

        return response()->json(['ok' => true]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($resp = $this->authorize($request, 'productos', 'crear')) {
            return $resp;
        }

        $productos = (string) config('gc.tables.productos', 'productos');
        if (!$this->schema->hasTable($productos)) {
            return response()->json(['ok' => false, 'error' => "Table not found: {$productos}"], 501);
        }

        $payload = $request->validate([
            'sku' => ['required', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'precio' => ['nullable', 'numeric'],
            'costo' => ['nullable', 'numeric'],
            'moneda' => ['nullable', 'string', 'max:3'],
            'imagen_url' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
            'stock_inicial' => ['nullable', 'array'],
            'stock_inicial.*.sucursal_id' => ['required_with:stock_inicial', 'integer', 'min:1'],
            'stock_inicial.*.cantidad' => ['required_with:stock_inicial', 'integer', 'min:1'],
            'stock_inicial.*.motivo' => ['nullable', 'string', 'max:150'],
        ]);

        $sku = Str::upper(trim((string) $payload['sku']));

        $id = DB::transaction(function () use ($productos, $payload, $sku, $request): int {
            $id = (int) DB::table($productos)->insertGetId([
                'sku' => $sku,
                'nombre' => $payload['nombre'],
                'descripcion' => $payload['descripcion'] ?? null,
                'categoria' => $payload['categoria'] ?? null,
                'modelo' => $payload['modelo'] ?? null,
                'precio' => $payload['precio'] ?? 0,
                'costo' => $payload['costo'] ?? 0,
                'moneda' => $payload['moneda'] ?? 'PEN',
                'imagen_url' => $payload['imagen_url'] ?? null,
                'activo' => $payload['activo'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $uid = (int) $request->attributes->get('remote_uid', 0);

            foreach (($payload['stock_inicial'] ?? []) as $row) {
                $this->stock->movimiento([
                    'producto_id' => $id,
                    'sucursal_id' => (int) ($row['sucursal_id'] ?? 0) ?: null,
                    'tipo' => 'ENTRADA',
                    'cantidad' => (int) ($row['cantidad'] ?? 0),
                    'motivo' => $row['motivo'] ?? 'Stock inicial',
                    'usuario_id' => $uid ?: null,
                ]);
            }

            return $id;
        });

        $fresh = DB::table($productos)->where('id', $id)->first();
        return response()->json(['ok' => true, 'data' => $fresh]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if ($resp = $this->authorize($request, 'productos', 'editar')) {
            return $resp;
        }

        $productos = (string) config('gc.tables.productos', 'productos');
        $stockSucursal = (string) config('gc.tables.stock_sucursal', 'stock_sucursal');

        if (!$this->schema->hasTable($productos)) {
            return response()->json(['ok' => false, 'error' => "Table not found: {$productos}"], 501);
        }

        $payload = $request->validate([
            'sku' => ['nullable', 'string', 'max:50'],
            'nombre' => ['nullable', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'precio' => ['nullable', 'numeric'],
            'costo' => ['nullable', 'numeric'],
            'moneda' => ['nullable', 'string', 'max:3'],
            'imagen_url' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
            'stock_meta' => ['nullable', 'array'],
            'stock_meta.*.sucursal_id' => ['required_with:stock_meta', 'integer', 'min:1'],
            'stock_meta.*.stock_min' => ['nullable', 'integer', 'min:0'],
            'stock_meta.*.ubicacion' => ['nullable', 'string', 'max:60'],
        ]);

        $row = DB::table($productos)->where('id', $id)->first();
        if (!$row) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        DB::transaction(function () use ($productos, $stockSucursal, $id, $payload) {
            $update = [];
            foreach (['sku', 'nombre', 'descripcion', 'categoria', 'modelo', 'precio', 'costo', 'moneda', 'activo'] as $k) {
                if (array_key_exists($k, $payload) && $payload[$k] !== null) {
                    $update[$k] = $k === 'sku' ? Str::upper(trim((string) $payload[$k])) : $payload[$k];
                }
            }
            // Permitir limpiar imagen_url (remove_imagen => null)
            if (array_key_exists('imagen_url', $payload)) {
                $update['imagen_url'] = $payload['imagen_url'];
            }
            if ($update !== []) {
                $update['updated_at'] = now();
                DB::table($productos)->where('id', $id)->update($update);
            }

            if ($this->schema->hasTable($stockSucursal)) {
                foreach (($payload['stock_meta'] ?? []) as $meta) {
                    $sid = (int) ($meta['sucursal_id'] ?? 0);
                    if ($sid <= 0) {
                        continue;
                    }
                    $exists = DB::table($stockSucursal)->where('producto_id', $id)->where('sucursal_id', $sid)->first();
                    if (!$exists) {
                        DB::table($stockSucursal)->insert([
                            'producto_id' => $id,
                            'sucursal_id' => $sid,
                            'stock' => 0,
                            'stock_min' => 0,
                            'ubicacion' => null,
                            'updated_at' => now(),
                        ]);
                    }
                    $upd = [];
                    if (array_key_exists('stock_min', $meta) && $meta['stock_min'] !== null) {
                        $upd['stock_min'] = (int) $meta['stock_min'];
                    }
                    if (array_key_exists('ubicacion', $meta) && $meta['ubicacion'] !== null) {
                        $upd['ubicacion'] = $meta['ubicacion'];
                    }
                    if ($upd !== []) {
                        $upd['updated_at'] = now();
                        DB::table($stockSucursal)->where('producto_id', $id)->where('sucursal_id', $sid)->update($upd);
                    }
                }
            }
        });

        $fresh = DB::table($productos)->where('id', $id)->first();
        return response()->json(['ok' => true, 'data' => $fresh]);
    }

    public function destroy(int $id): JsonResponse
    {
        $uid = (int) request()?->attributes->get('remote_uid', 0);
        if (!$this->isAdmin($uid)) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $productos = (string) config('gc.tables.productos', 'productos');
        if (!$this->schema->hasTable($productos)) {
            return response()->json(['ok' => false, 'error' => "Table not found: {$productos}"], 501);
        }

        $row = DB::table($productos)->where('id', $id)->first();
        if (!$row) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        DB::table($productos)->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    public function import(Request $request): JsonResponse
    {
        if ($resp = $this->authorize($request, 'productos', 'crear')) {
            return $resp;
        }

        $productos = (string) config('gc.tables.productos', 'productos');
        if (!$this->schema->hasTable($productos)) {
            return response()->json(['ok' => false, 'error' => "Table not found: {$productos}"], 501);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $file = $request->file('file');
        $content = $file ? file_get_contents($file->getRealPath()) : '';
        if ($content === false || trim($content) === '') {
            return response()->json(['ok' => false, 'error' => 'Archivo vacío'], 422);
        }

        $lines = preg_split("/\\r\\n|\\n|\\r/", trim($content)) ?: [];
        if ($lines === []) {
            return response()->json(['ok' => false, 'error' => 'Archivo inválido'], 422);
        }

        $header = str_getcsv(array_shift($lines));
        $map = [];
        foreach ($header as $i => $col) {
            $map[Str::lower(trim($col))] = $i;
        }
        foreach (['sku', 'nombre', 'precio'] as $need) {
            if (!array_key_exists($need, $map)) {
                return response()->json(['ok' => false, 'error' => "Falta columna: {$need}"], 422);
            }
        }

        $ok = 0;
        $err = 0;
        $errors = [];

        DB::transaction(function () use ($lines, $map, $productos, &$ok, &$err, &$errors) {
            foreach ($lines as $idx => $line) {
                $row = str_getcsv($line);
                $sku = Str::upper(trim((string) ($row[$map['sku']] ?? '')));
                $nombre = trim((string) ($row[$map['nombre']] ?? ''));
                $precio = (string) ($row[$map['precio']] ?? '0');

                if ($sku === '' || $nombre === '') {
                    $err++;
                    $errors[] = ['line' => $idx + 2, 'error' => 'SKU o nombre vacío'];
                    continue;
                }

                $precioNum = is_numeric($precio) ? (float) $precio : 0.0;

                $existing = DB::table($productos)->where('sku', $sku)->first();
                if ($existing) {
                    DB::table($productos)->where('id', $existing->id)->update([
                        'nombre' => $nombre,
                        'precio' => $precioNum,
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table($productos)->insert([
                        'sku' => $sku,
                        'nombre' => $nombre,
                        'precio' => $precioNum,
                        'costo' => 0,
                        'moneda' => 'PEN',
                        'activo' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $ok++;
            }
        });

        return response()->json([
            'ok' => true,
            'data' => [
                'rows_ok' => $ok,
                'rows_error' => $err,
                'errors' => $errors,
            ],
        ]);
    }

    private function authorize(Request $request, string $module, string $action): ?JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        if ($uid <= 0) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
        }

        if ($this->rbac->can($uid, $module, $action)) {
            return null;
        }

        return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
    }

    private function isAdmin(int $uid): bool
    {
        if ($uid <= 0) {
            return false;
        }
        $u = DB::table('usuarios')->leftJoin('roles', 'roles.id', '=', 'usuarios.rol_id')
            ->where('usuarios.id', $uid)
            ->select(['roles.codigo as rol_codigo'])
            ->first();
        return $u && (string) ($u->rol_codigo ?? '') === 'administrador';
    }
}
