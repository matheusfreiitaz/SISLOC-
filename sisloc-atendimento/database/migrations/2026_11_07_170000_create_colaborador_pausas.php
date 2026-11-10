<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pausas_colaboradores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->timestamp('iniciada_em');
            $table->timestamp('prevista_ate');
            $table->timestamp('retornada_em')->nullable();
            $table->timestamps();
            $table->index(['retornada_em', 'prevista_ate']);
            $table->index(['usuario_id', 'retornada_em']);
        });

        Schema::create('configuracoes_pausa', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('limite_simultaneas')->default(10);
            $table->unsignedSmallInteger('duracao_minutos')->default(15);
            $table->timestamps();
        });

        DB::table('configuracoes_pausa')->insert([
            'id' => 1,
            'limite_simultaneas' => 10,
            'duracao_minutos' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pausas_colaboradores');
        Schema::dropIfExists('configuracoes_pausa');
    }
};
