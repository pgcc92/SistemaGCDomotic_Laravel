<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('comisiones') || !Schema::hasTable('ventas')) {
            return;
        }

        DB::statement("
            update comisiones c
            set periodo = to_char(v.fecha_venta::date, 'YYYY-MM'),
                updated_at = now()
            from ventas v
            where v.id = c.venta_id
              and v.fecha_venta is not null
              and c.periodo is distinct from to_char(v.fecha_venta::date, 'YYYY-MM')
        ");

        DB::statement("
            update comisiones c
            set monto_pen = c.monto_comision,
                updated_at = now()
            from ventas v
            where v.id = c.venta_id
              and c.estado = 'PENDIENTE'
              and c.monto_comision is not null
              and c.monto_pen is not null
              and abs(c.monto_pen - coalesce(v.total_pen, v.total, 0)) < 0.01
        ");
    }

    public function down(): void
    {
    }
};
