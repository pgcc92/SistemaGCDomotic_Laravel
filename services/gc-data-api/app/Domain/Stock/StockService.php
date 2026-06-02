<?php

namespace App\Domain\Stock;

use Illuminate\Support\Facades\DB;

final class StockService
{
    /**
     * Descuenta stock para una venta desde la sucursal preferida y, si no alcanza,
     * desde otras sucursales con stock. Devuelve las asignaciones realizadas.
     *
     * @return array<int,array{sucursal_id:int,cantidad:int}>
     */
    public function ventaDesdeCualquierSucursal(int $productoId, int $cantidad, int $sucursalPreferidaId, ?int $ventaId = null, ?int $usuarioId = null, ?string $motivo = null): array
    {
        $stockTable = (string) config('gc.tables.stock_sucursal', 'stock_sucursal');

        $productoId = (int) $productoId;
        $cantidad = (int) $cantidad;
        $sucursalPreferidaId = (int) $sucursalPreferidaId;

        if ($productoId <= 0 || $cantidad <= 0 || $sucursalPreferidaId <= 0) {
            throw new \InvalidArgumentException('Parámetros inválidos para venta');
        }

        $allocations = [];
        $remaining = $cantidad;

        // Candidatos: preferida primero, luego otras con mayor stock
        $candidates = DB::table($stockTable)
            ->where('producto_id', $productoId)
            ->where('stock', '>', 0)
            ->orderByRaw('case when sucursal_id = ? then 0 else 1 end', [$sucursalPreferidaId])
            ->orderByDesc('stock')
            ->pluck('sucursal_id')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        if (!in_array($sucursalPreferidaId, $candidates, true)) {
            array_unshift($candidates, $sucursalPreferidaId);
        }

        foreach ($candidates as $sid) {
            if ($remaining <= 0) {
                break;
            }

            $stockNow = (int) (DB::table($stockTable)
                ->where('producto_id', $productoId)
                ->where('sucursal_id', $sid)
                ->value('stock') ?? 0);

            if ($stockNow <= 0) {
                continue;
            }

            $take = min($remaining, $stockNow);
            if ($take <= 0) {
                continue;
            }

            // Este método valida no-negativo y actualiza stock + inserta kardex
            $this->movimiento([
                'tipo' => 'VENTA',
                'producto_id' => $productoId,
                'cantidad' => $take,
                'sucursal_origen' => $sid,
                'venta_id' => $ventaId,
                'usuario_id' => $usuarioId,
                'motivo' => $motivo ?: 'Venta',
            ]);

            $allocations[] = ['sucursal_id' => $sid, 'cantidad' => $take];
            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new \InvalidArgumentException('Stock insuficiente (total) para completar la venta');
        }

        return $allocations;
    }

    public function movimiento(array $payload): void
    {
        $movTable = (string) config('gc.tables.movimientos_stock', 'movimientos_stock');
        $stockTable = (string) config('gc.tables.stock_sucursal', 'stock_sucursal');
        $useTriggers = (bool) config('gc.stock.use_triggers', false);

        $tipo = (string) ($payload['tipo'] ?? '');
        $productoId = (int) ($payload['producto_id'] ?? 0);
        $cantidad = (int) ($payload['cantidad'] ?? 0);
        $sucursal = $payload['sucursal_id'] ?? null; // compat
        $sucursalOrigen = $payload['sucursal_origen'] ?? null;
        $sucursalDestino = $payload['sucursal_destino'] ?? null;
        $ventaId = $payload['venta_id'] ?? null;
        $usuarioId = $payload['usuario_id'] ?? null;
        $motivo = $payload['motivo'] ?? null;

        $tipo = strtoupper(trim($tipo));
        $allowed = ['ENTRADA', 'SALIDA', 'TRANSFER', 'AJUSTE', 'VENTA', 'DEVOLUCION'];

        if ($productoId <= 0 || $cantidad <= 0 || $tipo === '' || !in_array($tipo, $allowed, true)) {
            throw new \InvalidArgumentException('Movimiento inválido');
        }

        // Normalizar sucursales por tipo (compat con sucursal_id)
        $ori = $sucursalOrigen ?? null;
        $dst = $sucursalDestino ?? null;
        if ($ori === null && $dst === null && $sucursal !== null) {
            $ori = in_array($tipo, ['SALIDA', 'TRANSFER', 'AJUSTE', 'VENTA'], true) ? $sucursal : null;
            $dst = in_array($tipo, ['ENTRADA', 'TRANSFER', 'DEVOLUCION'], true) ? $sucursal : null;
        }

        // Validaciones de negocio
        if (in_array($tipo, ['SALIDA', 'AJUSTE', 'VENTA'], true) && ($ori === null || (int) $ori <= 0)) {
            throw new \InvalidArgumentException('Sucursal origen requerida');
        }
        if (in_array($tipo, ['ENTRADA', 'DEVOLUCION'], true) && ($dst === null || (int) $dst <= 0)) {
            throw new \InvalidArgumentException('Sucursal destino requerida');
        }
        if ($tipo === 'TRANSFER') {
            if ($ori === null || $dst === null || (int) $ori <= 0 || (int) $dst <= 0) {
                throw new \InvalidArgumentException('Sucursales origen y destino requeridas');
            }
            if ((int) $ori === (int) $dst) {
                throw new \InvalidArgumentException('La sucursal origen y destino no pueden ser la misma.');
            }
        }

        DB::transaction(function () use ($movTable, $stockTable, $useTriggers, $tipo, $productoId, $cantidad, $ori, $dst, $motivo, $ventaId, $usuarioId) {
            // Lock rows necesarias y validar stock disponible (no negativo)
            $ensureRow = function (int $sucursalId) use ($stockTable, $productoId) {
                $row = DB::table($stockTable)
                    ->where('producto_id', $productoId)
                    ->where('sucursal_id', $sucursalId)
                    ->lockForUpdate()
                    ->first();

                if (!$row) {
                    DB::table($stockTable)->insert([
                        'producto_id' => $productoId,
                        'sucursal_id' => $sucursalId,
                        'stock' => 0,
                        'stock_min' => 0,
                        'ubicacion' => null,
                        'updated_at' => now(),
                    ]);

                    $row = DB::table($stockTable)
                        ->where('producto_id', $productoId)
                        ->where('sucursal_id', $sucursalId)
                        ->lockForUpdate()
                        ->first();
                }

                return $row;
            };

            $oriId = $ori !== null ? (int) $ori : null;
            $dstId = $dst !== null ? (int) $dst : null;

            $oriRow = $oriId ? $ensureRow($oriId) : null;
            $dstRow = $dstId ? $ensureRow($dstId) : null;

            if ($oriRow) {
                $stockOri = (int) ($oriRow->stock ?? 0);
                $deltaOri = in_array($tipo, ['SALIDA', 'VENTA', 'TRANSFER', 'AJUSTE'], true) ? -$cantidad : 0;
                if ($deltaOri < 0 && ($stockOri + $deltaOri) < 0) {
                    throw new \InvalidArgumentException('Stock insuficiente en sucursal origen');
                }
                if (!$useTriggers && $deltaOri !== 0) {
                    DB::table($stockTable)
                        ->where('producto_id', $productoId)
                        ->where('sucursal_id', $oriId)
                        ->update(['stock' => $stockOri + $deltaOri, 'updated_at' => now()]);
                }
            }

            if ($dstRow) {
                $stockDst = (int) ($dstRow->stock ?? 0);
                $deltaDst = in_array($tipo, ['ENTRADA', 'DEVOLUCION', 'TRANSFER'], true) ? +$cantidad : 0;
                if (!$useTriggers && $deltaDst !== 0) {
                    DB::table($stockTable)
                        ->where('producto_id', $productoId)
                        ->where('sucursal_id', $dstId)
                        ->update(['stock' => $stockDst + $deltaDst, 'updated_at' => now()]);
                }
            }

            DB::table($movTable)->insert([
                'producto_id' => $productoId,
                'sucursal_origen' => $oriId,
                'sucursal_destino' => $dstId,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'motivo' => $motivo,
                'venta_id' => $ventaId,
                'usuario_id' => $usuarioId,
                'created_at' => now(),
            ]);
        });
    }
}
