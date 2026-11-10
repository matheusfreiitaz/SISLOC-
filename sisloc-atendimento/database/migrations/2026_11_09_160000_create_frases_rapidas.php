<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frases_rapidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('titulo', 80);
            $table->text('conteudo');
            $table->timestamps();
            $table->unique(['usuario_id', 'titulo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frases_rapidas');
    }
};
