<?php

namespace App\Http\Controllers\Api\V1;

use App\Infrastructure\Db\SchemaIntrospector;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class StockController
{
    public function __construct(
        private readonly SchemaIntrospector $schema,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $productos = (string) config('gc.tables.productos', 'productos');
        $stockSucursal = (string) config('gc.tables.stock_sucursal', 'stock_sucursal');

        if (!$this->schema->hasTable($productos) || !$this->schema->hasTable($stockSucursal)) {
            return response()->json([
                'ok' => false,
                'error' => "Tables not found: {$productos} / {$stockSucursal}",
            ], 501);
        }

        $rows = DB::table("{$productos} as p")
            ->leftJoin("{$stockSucursal} as ss", 'ss.producto_id', '=', 'p.id')
            ->where('p.activo', true)
            ->groupBy('p.id', 'p.sku', 'p.nombre', 'p.modelo', 'p.moneda', 'p.precio', 'p.imagen_url', 'p.updated_at')
            ->selectRaw('p.id, p.sku, p.nombre, p.modelo, p.moneda, p.precio, p.imagen_url, p.updated_at')
            ->selectRaw('coalesce(sum(ss.stock), 0) as stock_total')
            ->selectRaw('coalesce(sum(ss.stock_min), 0) as stock_min_total')
            ->selectRaw('coalesce(bool_or(ss.stock < ss.stock_min), false) as stock_bajo')
            ->orderBy('p.id', 'desc')
            ->limit(500)
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $rows,
        ]);
    }
}
