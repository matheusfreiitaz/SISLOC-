<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interacoes_edicoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interacao_id')->constrained('interacoes_chamado')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->text('mensagem_anterior');
            $table->timestamps();
            $table->index(['interacao_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interacoes_edicoes');
    }
};
