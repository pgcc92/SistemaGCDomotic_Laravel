<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Comisiones\ComisionService;
use App\Domain\Rbac\RbacService;
use App\Domain\Stock\StockService;
use App\Domain\Ventas\VentaService;
use App\Infrastructure\Db\SchemaIntrospector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class VentasController
{
    public function __construct(
        private readonly SchemaIntrospector $schema,
        private readonly VentaService $ventaService,
        private readonly StockService $stock,
        private readonly ComisionService $comisiones,
        private readonly RbacService $rbac,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $table = (string) config('gc.tables.ventas', 'ventas');
        if (!$this->schema->hasTable($table)) {
            return response()->json(['ok' => false, 'error' => "Table not found: {$table}"], 501);
        }

        $uid = (int) $request->attributes->get('remote_uid', 0);
        $canViewAll = $uid > 0 ? $this->rbac->canViewAll($uid, 'ventas') : false;
        $isAdmin = $uid > 0 ? $this->rbac->isAdmin($uid) : false;

        $limit = max(1, min(200, (int) $request->query('limit', 50)));
        $qText = trim((string) $request->query('q', ''));

        $clientes = (string) config('gc.tables.clientes', 'clientes');
        $hasClientes = $this->schema->hasTable($clientes);

        $q = DB::table($table . ' as v')->orderBy('v.id', 'desc');
        if ($hasClientes) {
            $q->leftJoin($clientes . ' as c', 'c.id', '=', 'v.cliente_id')
                ->select([
                    'v.*',
                    'c.telefono as cliente_telefono',
                    'c.nombre as cliente_nombre',
                    'c.razon_social as cliente_razon_social',
                    'c.tipo_documento as cliente_tipo_documento',
                    'c.numero_documento as cliente_numero_documento',
                    'c.email as cliente_email',
                    'c.direccion as cliente_direccion_ref',
                ]);
        } else {
            $q->select('v.*');
        }

        // Filtros
        if (!$canViewAll && $uid > 0) {
            $q->where('v.vendedor_id', $uid);
        }

        if ($tipo = $request->query('tipo_documento')) {
            $q->where('v.tipo_documento', $tipo);
        }
        if ($estado = $request->query('estado')) {
            $q->where('v.estado', $estado);
        }
        if ($sucursal = $request->query('sucursal_id')) {
            $q->where('v.sucursal_id', $sucursal);
        }
        if (($isAdmin || $canViewAll) && ($v = $request->query('vendedor_id'))) {
            $q->where('v.vendedor_id', $v);
        }
        if ($from = $request->query('from')) {
            $q->where('v.fecha_venta', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $q->where('v.fecha_venta', '<=', $to);
        }

        if ($qText !== '') {
            $qq = '%' . mb_strtolower($qText) . '%';
            $q->where(function ($w) use ($qq, $hasClientes) {
                $w->orWhereRaw('lower(coalesce(v.venta_codigo,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(v.ticket_id,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(v.tipo_documento,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(v.serie_documento,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(v.numero_documento,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(v.cliente_doc_num,\'\')) like ?', [$qq])
                    ->orWhereRaw('lower(coalesce(v.cliente_razon,\'\')) like ?', [$qq]);

                if ($hasClientes) {
                    $w->orWhereRaw('lower(coalesce(c.telefono,\'\')) like ?', [$qq])
                        ->orWhereRaw('lower(coalesce(c.nombre,\'\')) like ?', [$qq])
                        ->orWhereRaw('lower(coalesce(c.razon_social,\'\')) like ?', [$qq])
                        ->orWhereRaw('lower(coalesce(c.tipo_documento,\'\')) like ?', [$qq])
                        ->orWhereRaw('lower(coalesce(c.numero_documento,\'\')) like ?', [$qq])
                        ->orWhereRaw('lower(coalesce(c.email,\'\')) like ?', [$qq])
                        ->orWhereRaw('lower(coalesce(c.direccion,\'\')) like ?', [$qq]);
                }
            });
        }

        $rows = $q->limit($limit)->get();

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function show(int|string $id): JsonResponse
    {
        $table = (string) config('gc.tables.ventas', 'ventas');
        if (!$this->schema->hasTable($table)) {
            return response()->json(['ok' => false, 'error' => "Table not found: {$table}"], 501);
        }

        $ventaItems = (string) config('gc.tables.venta_items', 'venta_items');
        $pagos = (string) config('gc.tables.pagos', 'pagos');

        $uid = (int) request()?->attributes->get('remote_uid', 0);
        $row = DB::table($table)->where('id', $id)->first();
        if (!$row) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        $canViewAll = $uid > 0 ? $this->rbac->canViewAll($uid, 'ventas') : false;

        if (!$canViewAll && $uid > 0 && (int) $row->vendedor_id !== $uid) {
            return response()->json(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $items = $this->schema->hasTable($ventaItems)
            ? DB::table($ventaItems)->where('venta_id', $row->id)->orderBy('id')->get()
            : collect();
        $pagosRows = $this->schema->hasTable($pagos)
            ? DB::table($pagos)->where('venta_id', $row->id)->orderBy('id')->get()
            : collect();
        $clientes = (string) config('gc.tables.clientes', 'clientes');
        $cliente = null;
        if ($this->schema->hasTable($clientes) && (int) ($row->cliente_id ?? 0) > 0) {
            $cliente = DB::table($clientes)->where('id', (int) $row->cliente_id)->first();
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'venta' => $row,
                'cliente' => $cliente,
                'items' => $items,
                'pagos' => $pagosRows,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'cliente_id' => ['nullable', 'integer'],
            'vendedor_id' => ['nullable', 'integer'],
            'ticket_id' => ['nullable', 'string', 'max:50'],
            'sucursal_id' => ['nullable', 'integer'],
            'canal' => ['nullable', 'string', 'max:50'],
            'tipo_documento' => ['nullable', 'string', 'max:20'],
            'serie_documento' => ['nullable', 'string', 'max:10'],
            'numero_documento' => ['nullable', 'string', 'max:20'],
            'cliente_doc_tipo' => ['nullable', 'string', 'max:10'],
            'cliente_doc_num' => ['nullable', 'string', 'max:20'],
            'cliente_razon' => ['nullable', 'string', 'max:200'],
            'cliente_direccion' => ['nullable', 'string'],
            'moneda' => ['nullable', 'string', 'size:3'],
            'tipo_cambio' => ['nullable', 'numeric'],
            'igv_porcentaje' => ['nullable', 'numeric'],
            'descuento' => ['nullable', 'numeric'],
            'metodo_pago' => ['nullable', 'string', 'max:40'],
            'pago_referencia' => ['nullable', 'string', 'max:100'],
            'comprobante_url' => ['nullable', 'string'],
            'estado' => ['nullable', 'string', 'max:30'],
            'notas' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['nullable', 'integer'],
            'items.*.descripcion' => ['nullable', 'string', 'max:255'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_unit' => ['required', 'numeric', 'min:0'],
            'items.*.descuento' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Auditoría / autoría de acciones (si está disponible)
        $remoteUid = (int) $request->attributes->get('remote_uid', 0);
        if ($remoteUid > 0) {
            $payload['registrado_por'] = $remoteUid;
        }

        try {
            $result = $this->ventaService->crear($payload);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'data' => $result,
        ], 201);
    }

    public function stats(Request $request): JsonResponse
    {
        $uid = (int) $request->attributes->get('remote_uid', 0);
        $canViewAll = $uid > 0 ? $this->rbac->canViewAll($uid, 'ventas') : false;

        $q = DB::table('ventas');
        if (!$canViewAll && $uid > 0) {
            $q->where('vendedor_id', $uid);
        }

        if ($from = $request->query('from')) {
            $q->where('fecha_venta', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $q->where('fecha_venta', '<=', $to);
        }

        $byTipo = (clone $q)->selectRaw('tipo_documento, count(*) as cantidad, coalesce(sum(total),0) as total')
            ->groupBy('tipo_documento')
            ->orderBy('tipo_documento')
            ->get();

        $byEstado = (clone $q)->selectRaw('estado, count(*) as cantidad, coalesce(sum(total),0) as total')
            ->groupBy('estado')
            ->orderBy('estado')
            ->get();

        $totalMes = (float) (clone $q)
            ->whereRaw("upper(coalesce(estado,'')) <> 'ANULADA'")
            ->sum('total');
        $pagadasMes = (int) (clone $q)
            ->whereRaw("upper(coalesce(estado,'')) = 'PAGADA'")
            ->count();
        $pendientesMes = (int) (clone $q)
            ->whereRaw("upper(coalesce(estado,'')) = 'PENDIENTE'")
            ->count();
        $moneda = (string) ((clone $q)->value('moneda') ?: 'PEN');

        return response()->json([
            'ok' => true,
            'data' => [
                'by_tipo' => $byTipo,
                'by_estado' => $byEstado,
                'total_mes' => round($totalMes, 2),
                'pagadas_mes' => $pagadasMes,
                'pendientes_mes' => $pendientesMes,
                'moneda' => $moneda,
            ],
        ]);
    }

    public function nextCorrelativo(Request $request): JsonResponse
    {
        $tipo = (string) $request->query('tipo_documento', 'NOTA_VENTA');
        $serie = (string) $request->query('serie', config('gc.ventas.default_serie_nota_venta', 'NV01'));

        $row = DB::table('documento_series')
            ->where('tipo_documento', $tipo)
            ->where('serie', $serie)
            ->where('activo', true)
            ->first();

        if (!$row) {
            return response()->json(['ok' => false, 'error' => 'Serie no encontrada'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'tipo_documento' => $tipo,
                'serie' => $serie,
                'next' => ((int) $row->correlativo_actual) + 1,
            ],
        ]);
    }

    public function pagar(Request $request, int $id): JsonResponse
    {
        $venta = DB::table('ventas')->where('id', $id)->first();
        if (!$venta) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        if ((string) $venta->estado === 'ANULADA') {
            return response()->json(['ok' => false, 'error' => 'Venta anulada'], 422);
        }

        $payload = $request->validate([
            'metodo' => ['required', 'string', 'max:40'],
            'referencia' => ['nullable', 'string', 'max:100'],
        ]);

        $warnings = DB::transaction(function () use ($venta, $payload) {
            DB::table('ventas')->where('id', $venta->id)->update([
                'estado' => 'PAGADA',
                'metodo_pago' => $payload['metodo'],
                'updated_at' => now(),
            ]);

            DB::table('pagos')->insert([
                'venta_id' => $venta->id,
                'monto' => $venta->total,
                'metodo' => $payload['metodo'],
                'referencia' => $payload['referencia'] ?? null,
                'fecha_pago' => now(),
                'registrado_por' => (int) request()?->attributes->get('remote_uid', 0) ?: null,
            ]);

            $items = DB::table('venta_items')->where('venta_id', $venta->id)->get();
            $warnings = [];
            foreach ($items as $it) {
                if (!$it->producto_id || !$venta->sucursal_id) {
                    continue;
                }

                $allocs = $this->stock->ventaDesdeCualquierSucursal(
                    (int) $it->producto_id,
                    (int) $it->cantidad,
                    (int) $venta->sucursal_id,
                    (int) $venta->id,
                    (int) request()?->attributes->get('remote_uid', 0) ?: null,
                    'Pago de venta'
                );

                $usedOther = array_values(array_filter($allocs, fn ($a) => (int) $a['sucursal_id'] !== (int) $venta->sucursal_id));
                if ($usedOther !== []) {
                    $otherIds = array_values(array_unique(array_map(fn ($a) => (int) $a['sucursal_id'], $usedOther)));
                    $sucInfo = DB::table('sucursales')
                        ->whereIn('id', $otherIds)
                        ->get(['id', 'codigo', 'nombre'])
                        ->keyBy('id');

                    $sucursalesUsadas = array_map(function ($a) use ($sucInfo) {
                        $sid = (int) $a['sucursal_id'];
                        $row = $sucInfo->get($sid);
                        return [
                            'sucursal_id' => $sid,
                            'cantidad' => (int) $a['cantidad'],
                            'sucursal_codigo' => $row?->codigo,
                            'sucursal_nombre' => $row?->nombre,
                        ];
                    }, $usedOther);

                    $warnings[] = [
                        'producto_id' => (int) $it->producto_id,
                        'descripcion' => (string) ($it->descripcion ?? null),
                        'cantidad_requerida' => (int) $it->cantidad,
                        'sucursal_seleccionada_id' => (int) $venta->sucursal_id,
                        'sucursales_usadas' => $sucursalesUsadas,
                    ];
                }
            }

            $this->comisiones->generarPorVenta((int) $venta->id);

            return $warnings;
        });

        return response()->json(['ok' => true, 'data' => ['warnings' => $warnings]]);
    }

    public function anular(Request $request, int $id): JsonResponse
    {
        $venta = DB::table('ventas')->where('id', $id)->first();
        if (!$venta) {
            return response()->json(['ok' => false, 'error' => 'Not found'], 404);
        }

        DB::transaction(function () use ($venta) {
            $wasPaid = (string) $venta->estado === 'PAGADA';

            DB::table('ventas')->where('id', $venta->id)->update([
                'estado' => 'ANULADA',
                'updated_at' => now(),
            ]);

            if ($wasPaid) {
                // Reversar según las sucursales realmente descontadas (movimientos_stock tipo=VENTA)
                $movs = DB::table('movimientos_stock')
                    ->where('venta_id', $venta->id)
                    ->where('tipo', 'VENTA')
                    ->get();

                foreach ($movs as $mv) {
                    if (!$mv->producto_id || !$mv->sucursal_origen) {
                        continue;
                    }
                    $this->stock->movimiento([
                        'tipo' => 'DEVOLUCION',
                        'producto_id' => (int) $mv->producto_id,
                        'cantidad' => (int) $mv->cantidad,
                        'sucursal_destino' => (int) $mv->sucursal_origen,
                        'venta_id' => (int) $venta->id,
                        'usuario_id' => (int) request()?->attributes->get('remote_uid', 0) ?: null,
                        'motivo' => 'Anulación de venta pagada',
                    ]);
                }
            }

            // Comisiones: se anulan si no pagadas
            DB::table('comisiones')
                ->where('venta_id', $venta->id)
                ->whereNotIn('estado', ['PAGADA'])
                ->update(['estado' => 'ANULADA', 'updated_at' => now()]);
        });

        return response()->json(['ok' => true]);
    }
}
