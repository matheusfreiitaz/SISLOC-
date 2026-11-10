<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chamados', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->string('numero')->nullable()->unique();
            $tabela->foreignId('solicitante_id')->constrained('usuarios');
            $tabela->foreignId('departamento_id')->constrained('departamentos');
            $tabela->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $tabela->foreignId('subcategoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $tabela->string('assunto');
            $tabela->text('descricao');
            $tabela->foreignId('prioridade_id')->constrained('prioridades');
            $tabela->foreignId('status_id')->constrained('status_chamado');
            $tabela->foreignId('agente_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $tabela->timestamp('prazo_em')->nullable();
            $tabela->timestamp('primeira_resposta_em')->nullable();
            $tabela->timestamp('finalizado_em')->nullable();
            $tabela->timestamps();
            $tabela->index(['status_id', 'departamento_id']);
        });

        Schema::create('interacoes_chamado', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('chamado_id')->constrained('chamados')->cascadeOnDelete();
            $tabela->foreignId('usuario_id')->constrained('usuarios');
            $tabela->string('tipo', 20)->default('resposta'); // resposta, nota_interna
            $tabela->text('mensagem');
            $tabela->timestamps();
        });

        Schema::create('anexos_chamado', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('chamado_id')->constrained('chamados')->cascadeOnDelete();
            $tabela->foreignId('interacao_id')->nullable()->constrained('interacoes_chamado')->cascadeOnDelete();
            $tabela->foreignId('usuario_id')->constrained('usuarios');
            $tabela->string('nome_original');
            $tabela->string('caminho');
            $tabela->string('mime')->nullable();
            $tabela->unsignedBigInteger('tamanho')->default(0);
            $tabela->timestamps();
        });

        Schema::create('historico_chamado', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('chamado_id')->constrained('chamados')->cascadeOnDelete();
            $tabela->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $tabela->string('campo');
            $tabela->text('valor_anterior')->nullable();
            $tabela->text('valor_novo')->nullable();
            $tabela->timestamp('created_at')->useCurrent();
        });

        Schema::create('finalizacoes_chamado', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('chamado_id')->constrained('chamados')->cascadeOnDelete();
            $tabela->foreignId('motivo_id')->constrained('motivos_finalizacao');
            $tabela->foreignId('usuario_id')->constrained('usuarios');
            $tabela->text('descricao_solucao');
            $tabela->text('observacoes')->nullable();
            $tabela->timestamps();
        });

        Schema::create('notificacoes', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $tabela->string('tipo', 40);
            $tabela->string('titulo');
            $tabela->text('mensagem')->nullable();
            $tabela->string('url')->nullable();
            $tabela->timestamp('lida_em')->nullable();
            $tabela->timestamps();
            $tabela->index(['usuario_id', 'lida_em']);
        });

        Schema::create('auditorias', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $tabela->string('acao', 60);
            $tabela->string('entidade')->nullable();
            $tabela->unsignedBigInteger('entidade_id')->nullable();
            $tabela->json('detalhes')->nullable();
            $tabela->string('ip', 45)->nullable();
            $tabela->timestamp('created_at')->useCurrent();
            $tabela->index(['acao', 'created_at']);
        });

        Schema::create('uso_inteligencia_artificial', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $tabela->foreignId('chamado_id')->nullable()->constrained('chamados')->nullOnDelete();
            $tabela->string('funcao', 60);
            $tabela->string('provedor', 40)->nullable();
            $tabela->unsignedInteger('tokens')->nullable();
            $tabela->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['uso_inteligencia_artificial', 'auditorias', 'notificacoes', 'finalizacoes_chamado', 'historico_chamado',
                  'anexos_chamado', 'interacoes_chamado', 'chamados'] as $nome) {
            Schema::dropIfExists($nome);
        }
    }
};
