<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoramento_execucoes', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->timestamp('iniciada_em');
            $tabela->timestamp('concluida_em')->nullable();
            $tabela->unsignedInteger('chamados_analisados')->default(0);
            $tabela->unsignedInteger('ocorrencias_identificadas')->default(0);
            $tabela->boolean('sucesso')->default(false);
            $tabela->text('erro')->nullable();
            $tabela->timestamps();
            $tabela->index(['sucesso', 'iniciada_em']);
        });

        Schema::create('monitoramento_alertas', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('chamado_id')->constrained('chamados')->cascadeOnDelete();
            $tabela->string('tipo', 60);
            $tabela->timestamp('detectado_em');
            $tabela->timestamp('resolvido_em')->nullable();
            $tabela->timestamps();
            $tabela->unique(['chamado_id', 'tipo']);
            $tabela->index(['resolvido_em', 'detectado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoramento_alertas');
        Schema::dropIfExists('monitoramento_execucoes');
    }
};
