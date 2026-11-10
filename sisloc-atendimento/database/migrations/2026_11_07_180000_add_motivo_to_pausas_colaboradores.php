<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pausas_colaboradores', function (Blueprint $table) {
            $table->string('motivo', 30)->nullable()->after('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::table('pausas_colaboradores', function (Blueprint $table) {
            $table->dropColumn('motivo');
        });
    }
};
