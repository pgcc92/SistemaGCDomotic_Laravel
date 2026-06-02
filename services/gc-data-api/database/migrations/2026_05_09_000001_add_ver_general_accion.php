<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('acciones')) {
            return;
        }

        $exists = DB::table('acciones')->where('codigo', 'ver_general')->exists();
        if (!$exists) {
            DB::table('acciones')->insert([
                'codigo' => 'ver_general',
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('acciones')) {
            return;
        }
        DB::table('acciones')->where('codigo', 'ver_general')->delete();
    }
};

