<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'remote_usuario_id')) {
                $table->unsignedBigInteger('remote_usuario_id')->nullable()->unique()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'remote_usuario_id')) {
                $table->dropUnique(['remote_usuario_id']);
                $table->dropColumn('remote_usuario_id');
            }
        });
    }
};

