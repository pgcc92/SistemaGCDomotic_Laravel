<?php

namespace App\Domain\Ventas;

use App\Domain\Comisiones\ComisionService;
use App\Domain\Stock\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class VentaService
{
    /** @param array<string,mixed> $payload */
    public function crear(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
            $ventas = (string) config('gc.tables.ventas', 'ventas');
            $ventaItems = (string) config('gc.tables.venta_items', 'venta_items');
            $documentoSeries = (string) config('gc.tables.documento_series', 'documento_series');
            $tipoCambioTable = (string) config('gc.tables.tipo_cambio', 'tipo_cambio');
            $pagos = (string) config('gc.tables.pagos', 'pagos');
            $stockSucursal = (string) config('gc.tables.stock_sucursal', 'stock_sucursal');
            $sucursalesTable = (string) config('gc.tables.sucursales', 'sucursales');

            $tipoDocumento = (string) ($payload['tipo_documento'] ?? 'NOTA_VENTA');
            $moneda = (string) ($payload['moneda'] ?? 'PEN');
            $igvPorcentaje = (float) ($payload['igv_porcentaje'] ?? (float) config('gc.ventas.igv_porcentaje_default', 18.00));
            $estado = (string) ($payload['estado'] ?? 'PENDIENTE');

            $items = $payload['items'] ?? null;
            if (!is_array($items) || $items === []) {
                throw new \InvalidArgumentException('items is required');
            }

            $sucursalId = $payload['sucursal_id'] ?? null;

            // Correlativos/serie/numero
            $serieDocumento = $payload['serie_documento'] ?? null;
            $numeroDocumento = $payload['numero_documento'] ?? null;

            if ($tipoDocumento === 'NOTA_VENTA') {
                $serieDocumento = $serieDocumento ?: (string) config('gc.ventas.default_serie_nota_venta', 'NV01');

                $serieRow = DB::table($documentoSeries)
                    ->where('tipo_documento', 'NOTA_VENTA')
                    ->where('serie', $serieDocumento)
                    ->where('activo', true)
                    ->when($sucursalId !== null, fn ($q) => $q->where(function ($qq) use ($sucursalId) {
                        $qq->whereNull('sucursal_id')->orWhere('sucursal_id', $sucursalId);
                    }))
                    ->lockForUpdate()
                    ->first();

                if (!$serieRow) {
                    // Si no existe la serie, no la creamos (no tocar esquema), pedimos que la creen/configuren.
                    throw new \RuntimeException("documento_series missing for NOTA_VENTA {$serieDocumento}");
                }

                $next = ((int) $serieRow->correlativo_actual) + 1;
                DB::table($documentoSeries)->where('id', $serieRow->id)->update(['correlativo_actual' => $next]);
                $numeroDocumento = (string) $next;
            } else {
                // Factura/Boleta: requieren serie y número según spec.
                if (!$serieDocumento || !$numeroDocumento) {
                    throw new \InvalidArgumentException('serie_documento and numero_documento are required for FACTURA/BOLETA');
                }
            }

            // Tipo de cambio
            $tipoCambio = $payload['tipo_cambio'] ?? null;
            if ($moneda === 'USD') {
                $tipoCambio = (float) $tipoCambio;
                if ($tipoCambio <= 0) {
                    $today = now()->toDateString();
                    $tc = DB::table($tipoCambioTable)->where('fecha', $today)->first();
                    $tipoCambio = $tc ? (float) $tc->venta : 0.0;
                }
            } else {
                $tipoCambio = $tipoCambio !== null ? (float) $tipoCambio : null;
            }

            // Totales (precios vienen CON IGV).
            $subtotal = 0.0;
            $itemsRows = [];
            foreach ($items as $i) {
                if (!is_array($i)) {
                    continue;
                }
                $cantidad = (int) ($i['cantidad'] ?? 1);
                $precioUnit = (float) ($i['precio_unit'] ?? 0);
                $descuentoItem = (float) ($i['descuento'] ?? 0);
                $totalItem = max(0.0, ($cantidad * $precioUnit) - $descuentoItem);
                $subtotal += $totalItem;

                $itemsRows[] = [
                    'producto_id' => $i['producto_id'] ?? null,
                    'descripcion' => (string) ($i['descripcion'] ?? ($i['nombre'] ?? 'Item')),
                    'cantidad' => $cantidad,
                    'precio_unit' => $precioUnit,
                    'descuento' => $descuentoItem,
                    'total' => $totalItem,
                ];
            }

            $descuento = (float) ($payload['descuento'] ?? 0);
            $total = max(0.0, $subtotal - $descuento);

            $baseImponible = 0.0;
            $igvMonto = 0.0;
            if (in_array($tipoDocumento, ['FACTURA', 'BOLETA'], true)) {
                $div = 1 + ($igvPorcentaje / 100);
                $baseImponible = $div > 0 ? round($total / $div, 2) : 0.0;
                $igvMonto = round($total - $baseImponible, 2);
            }

            $totalPen = null;
            if ($moneda === 'USD' && is_float($tipoCambio) && $tipoCambio > 0) {
                $totalPen = round($total * $tipoCambio, 2);
            }

            $ventaCodigo = (string) ($payload['venta_codigo'] ?? ('V-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6))));

            $ventaId = DB::table($ventas)->insertGetId([
                'venta_codigo' => $ventaCodigo,
                'cliente_id' => $payload['cliente_id'] ?? null,
                'vendedor_id' => $payload['vendedor_id'] ?? null,
                'ticket_id' => $payload['ticket_id'] ?? null,
                'sucursal_id' => $sucursalId,
                'canal' => $payload['canal'] ?? 'tienda',
                'tipo_documento' => $tipoDocumento,
                'serie_documento' => $serieDocumento,
                'numero_documento' => $numeroDocumento,
                'cliente_doc_tipo' => $payload['cliente_doc_tipo'] ?? null,
                'cliente_doc_num' => $payload['cliente_doc_num'] ?? null,
                'cliente_razon' => $payload['cliente_razon'] ?? null,
                'cliente_direccion' => $payload['cliente_direccion'] ?? null,
                'moneda' => $moneda,
                'tipo_cambio' => $tipoCambio,
                'igv_porcentaje' => $igvPorcentaje,
                'base_imponible' => $baseImponible,
                'igv_monto' => $igvMonto,
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'total' => $total,
                'total_pen' => $totalPen,
                'metodo_pago' => $payload['metodo_pago'] ?? null,
                'estado' => $estado,
                'notas' => $payload['notas'] ?? null,
                'fecha_venta' => $payload['fecha_venta'] ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($itemsRows as $row) {
                DB::table($ventaItems)->insert(array_merge($row, [
                    'venta_id' => $ventaId,
                ]));
            }

            // Si estado inicial PAGADA: registra pago (si hay método) y deja stock/comisión para el endpoint /dashboard.
            // (Implementación completa stock+comisiones se conectará al flujo del dashboard; por ahora registramos pago si viene.)
            if ($estado === 'PAGADA' && !empty($payload['metodo_pago'])) {
                DB::table($pagos)->insert([
                    'venta_id' => $ventaId,
                    'monto' => $total,
                    'metodo' => (string) $payload['metodo_pago'],
                    'referencia' => $payload['pago_referencia'] ?? null,
                    'comprobante_url' => $payload['comprobante_url'] ?? null,
                    'fecha_pago' => now(),
                    'registrado_por' => $payload['registrado_por'] ?? null,
                ]);
            }

            $venta = DB::table($ventas)->where('id', $ventaId)->first();
            $ventaItemsRows = DB::table($ventaItems)->where('venta_id', $ventaId)->get();

            // Warnings de stock:
            // - Siempre calculamos si la sucursal seleccionada no tiene stock suficiente y qué sedes tienen stock.
            // - Si la venta nace PAGADA, además descontamos "sí o sí" desde cualquier sede con stock (priorizando la seleccionada)
            //   y retornamos warnings con las sedes realmente usadas para el descuento.
            $warnings = [];
            if ($sucursalId !== null) {
                $needed = [];
                foreach ($itemsRows as $row) {
                    $pid = $row['producto_id'] ?? null;
                    if (!$pid) {
                        continue;
                    }
                    $pid = (int) $pid;
                    if ($pid <= 0) {
                        continue;
                    }
                    $needed[$pid] = ($needed[$pid] ?? 0) + (int) ($row['cantidad'] ?? 0);
                }

                $usuarioId = isset($payload['registrado_por']) ? (int) $payload['registrado_por'] : null;
                /** @var StockService $stock */
                $stock = app(StockService::class);

                foreach ($needed as $pid => $qtyNeed) {
                    $desc = null;
                    foreach ($itemsRows as $row) {
                        if ((int) ($row['producto_id'] ?? 0) === (int) $pid) {
                            $desc = (string) ($row['descripcion'] ?? null);
                            break;
                        }
                    }

                    // 1) Availability warning (sin descontar)
                    $current = DB::table($stockSucursal)
                        ->where('producto_id', (int) $pid)
                        ->where('sucursal_id', (int) $sucursalId)
                        ->value('stock');
                    $current = (int) ($current ?? 0);

                    if ($current < (int) $qtyNeed) {
                        $others = DB::table($stockSucursal . ' as ss')
                            ->leftJoin($sucursalesTable . ' as s', 's.id', '=', 'ss.sucursal_id')
                            ->where('ss.producto_id', (int) $pid)
                            ->where('ss.sucursal_id', '!=', (int) $sucursalId)
                            ->where('ss.stock', '>', 0)
                            ->orderByDesc('ss.stock')
                            ->limit(5)
                            ->get([
                                'ss.sucursal_id',
                                'ss.stock',
                                's.codigo as sucursal_codigo',
                                's.nombre as sucursal_nombre',
                            ]);

                        if ($others->count() > 0) {
                            $warnings[] = [
                                'producto_id' => (int) $pid,
                                'descripcion' => $desc,
                                'cantidad_requerida' => (int) $qtyNeed,
                                'stock_en_sucursal' => $current,
                                'sucursal_seleccionada_id' => (int) $sucursalId,
                                'sucursales_con_stock' => $others,
                            ];
                        }
                    }

                    // 2) Si está PAGADA, descontar desde cualquier sede y reportar sedes realmente usadas
                    if ($estado !== 'PAGADA') {
                        continue;
                    }

                    $allocs = $stock->ventaDesdeCualquierSucursal(
                        (int) $pid,
                        (int) $qtyNeed,
                        (int) $sucursalId,
                        (int) $ventaId,
                        $usuarioId,
                        'Pago de venta'
                    );

                    $usedOther = array_values(array_filter($allocs, fn ($a) => (int) $a['sucursal_id'] !== (int) $sucursalId));
                    if ($usedOther === []) {
                        continue;
                    }

                    $otherIds = array_values(array_unique(array_map(fn ($a) => (int) $a['sucursal_id'], $usedOther)));
                    $sucInfo = DB::table($sucursalesTable)
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
                        'producto_id' => (int) $pid,
                        'descripcion' => $desc,
                        'cantidad_requerida' => (int) $qtyNeed,
                        'sucursal_seleccionada_id' => (int) $sucursalId,
                        'sucursales_usadas' => $sucursalesUsadas,
                    ];
                }

                // Comisión por venta pagada
                if ($estado === 'PAGADA') {
                    /** @var ComisionService $comisiones */
                    $comisiones = app(ComisionService::class);
                    $comisiones->generarPorVenta((int) $ventaId);
                }
            }

            return [
                'venta' => $venta,
                'items' => $ventaItemsRows,
                'warnings' => $warnings,
            ];
        });
    }
}
