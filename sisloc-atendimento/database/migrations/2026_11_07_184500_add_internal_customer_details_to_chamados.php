<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chamados', function (Blueprint $table) {
            $table->text('resumo_atendimento')->nullable();
            $table->text('demandas_recorrentes')->nullable();
            $table->string('tom_comunicacao_cliente', 24)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('chamados', function (Blueprint $table) {
            $table->dropColumn(['resumo_atendimento', 'demandas_recorrentes', 'tom_comunicacao_cliente']);
        });
    }
};
