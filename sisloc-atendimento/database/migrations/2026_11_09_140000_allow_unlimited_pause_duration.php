<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pausas_colaboradores', function (Blueprint $table) {
            $table->timestamp('prevista_ate')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('pausas_colaboradores')->whereNull('prevista_ate')->update([
            'prevista_ate' => DB::raw('iniciada_em'),
        ]);
        DB::table('configuracoes_pausa')->where('duracao_minutos', 0)->update(['duracao_minutos' => 15]);

        Schema::table('pausas_colaboradores', function (Blueprint $table) {
            $table->timestamp('prevista_ate')->nullable(false)->change();
        });
    }
};
