<?php

namespace App\Domain\Comisiones;

use Illuminate\Support\Facades\DB;

final class ComisionService
{
    public function generarPorVenta(int $ventaId): void
    {
        $venta = DB::table('ventas')->where('id', $ventaId)->first();
        if (!$venta || !$venta->vendedor_id) {
            return;
        }

        // No duplicar
        if (DB::table('comisiones')->where('venta_id', $ventaId)->exists()) {
            return;
        }

        $vendedorId = (int) $venta->vendedor_id;

        // Regla: preferir por vendedor_id activa y vigente, luego por rol_aplica, luego ninguna.
        $today = now()->toDateString();
        $rule = DB::table('comision_reglas')
            ->where('activo', true)
            ->where('vigente_desde', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $today);
            })
            ->where(function ($q) use ($vendedorId) {
                $q->whereNull('vendedor_id')->orWhere('vendedor_id', $vendedorId);
            })
            ->orderByRaw('case when vendedor_id is null then 1 else 0 end')
            ->orderBy('id', 'desc')
            ->first();

        $porcentaje = $rule ? (float) ($rule->porcentaje ?? 0) : 0.0;
        $instaladorFee = $this->instaladorFeeDefault();

        // Base de cálculo (PEN): descuenta instalador y, para Factura/Boleta, desagrega IGV.
        $totalPen = (float) ($venta->total_pen ?? $venta->total ?? 0);
        $saldo = max(0.0, $totalPen - $instaladorFee);

        $tipoDoc = (string) ($venta->tipo_documento ?? 'NOTA_VENTA');
        $igvPct = (float) ($venta->igv_porcentaje ?? 18);

        $base = $saldo;
        if (in_array($tipoDoc, ['FACTURA', 'BOLETA'], true)) {
            $div = 1 + max(0.0, $igvPct) / 100;
            $base = $div > 0 ? ($saldo / $div) : $saldo;
        }

        $base = round($base, 2);
        $monto = $porcentaje > 0 ? round($base * ($porcentaje / 100), 2) : 0.0;

        $periodo = now()->format('Y-m');

        DB::table('comisiones')->insert([
            'venta_id' => $ventaId,
            'vendedor_id' => $vendedorId,
            'regla_id' => $rule?->id,
            'tipo_documento' => (string) $venta->tipo_documento,
            'base_calculo' => $base,
            'porcentaje' => $porcentaje,
            'monto_comision' => $monto,
            'moneda' => (string) $venta->moneda,
            'monto_pen' => $venta->total_pen,
            'estado' => 'PENDIENTE',
            'periodo' => $periodo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function instaladorFeeDefault(): float
    {
        // Configurable vía `app_config` (jsonb). Estructura sugerida:
        // clave = "comisiones", valor = {"instalador_fee_pen": 150}
        try {
            $row = DB::table('app_config')->where('clave', 'comisiones')->first();
            if (!$row) {
                return 0.0;
            }
            $val = $row->valor ?? null;
            $arr = is_array($val) ? $val : (is_string($val) ? json_decode($val, true) : null);
            $fee = is_array($arr) ? (float) ($arr['instalador_fee_pen'] ?? 0) : 0.0;
            return max(0.0, $fee);
        } catch (\Throwable) {
            return 0.0;
        }
    }
}
