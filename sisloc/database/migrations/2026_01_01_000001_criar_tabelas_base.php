<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departamentos', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->string('nome')->unique();
            $tabela->text('descricao')->nullable();
            $tabela->boolean('ativo')->default(true);
            $tabela->timestamps();
        });

        Schema::create('perfis', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->string('nome')->unique();
            $tabela->string('slug')->unique();
            $tabela->text('descricao')->nullable();
            $tabela->boolean('sistema')->default(false);
            $tabela->timestamps();
        });

        Schema::create('permissoes', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->string('chave')->unique();
            $tabela->string('modulo');
            $tabela->string('descricao');
            $tabela->timestamps();
        });

        Schema::create('perfil_permissao', function (Blueprint $tabela) {
            $tabela->foreignId('perfil_id')->constrained('perfis')->cascadeOnDelete();
            $tabela->foreignId('permissao_id')->constrained('permissoes')->cascadeOnDelete();
            $tabela->primary(['perfil_id', 'permissao_id']);
        });

        Schema::create('usuarios', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->string('nome');
            $tabela->string('email')->unique();
            $tabela->string('password');
            $tabela->foreignId('perfil_id')->constrained('perfis');
            $tabela->foreignId('departamento_id')->nullable()->constrained('departamentos')->nullOnDelete();
            $tabela->boolean('ativo')->default(true);
            $tabela->string('tema', 10)->default('claro');
            $tabela->string('foto_perfil')->nullable();
            $tabela->timestamp('ultimo_acesso_em')->nullable();
            $tabela->boolean('trocar_senha')->default(false);
            $tabela->rememberToken();
            $tabela->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $tabela) {
            $tabela->string('email')->primary();
            $tabela->string('token');
            $tabela->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $tabela) {
            $tabela->string('id')->primary();
            $tabela->foreignId('user_id')->nullable()->index();
            $tabela->string('ip_address', 45)->nullable();
            $tabela->text('user_agent')->nullable();
            $tabela->longText('payload');
            $tabela->integer('last_activity')->index();
        });

        Schema::create('categorias', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->string('nome');
            $tabela->foreignId('categoria_pai_id')->nullable()->constrained('categorias')->nullOnDelete();
            $tabela->foreignId('departamento_id')->nullable()->constrained('departamentos')->nullOnDelete();
            $tabela->boolean('ativo')->default(true);
            $tabela->timestamps();
        });

        Schema::create('prioridades', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->string('nome')->unique();
            $tabela->string('cor', 9)->default('#f97316');
            $tabela->unsignedInteger('prazo_horas')->default(24);
            $tabela->unsignedTinyInteger('nivel')->default(1);
            $tabela->boolean('ativo')->default(true);
            $tabela->timestamps();
        });

        Schema::create('status_chamado', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->string('nome')->unique();
            $tabela->string('slug')->unique();
            $tabela->string('cor', 9)->default('#64748b');
            $tabela->boolean('final')->default(false);
            $tabela->boolean('ativo')->default(true);
            $tabela->unsignedTinyInteger('ordem')->default(0);
            $tabela->timestamps();
        });

        Schema::create('motivos_finalizacao', function (Blueprint $tabela) {
            $tabela->id();
            $tabela->string('nome')->unique();
            $tabela->boolean('ativo')->default(true);
            $tabela->timestamps();
        });

        Schema::create('configuracoes', function (Blueprint $tabela) {
            $tabela->string('chave')->primary();
            $tabela->text('valor')->nullable();
            $tabela->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['configuracoes', 'motivos_finalizacao', 'status_chamado', 'prioridades', 'categorias', 'sessions',
                  'password_reset_tokens', 'usuarios', 'perfil_permissao', 'permissoes', 'perfis', 'departamentos'] as $nome) {
            Schema::dropIfExists($nome);
        }
    }
};
