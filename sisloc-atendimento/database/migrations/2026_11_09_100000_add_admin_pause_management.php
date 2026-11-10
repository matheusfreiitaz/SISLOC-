<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $motivosIniciais = [
        'Banheiro',
        'Almoço',
        'Café da tarde',
        'Café da manhã',
        'Feedback',
        'Reunião',
    ];

    public function up(): void
    {
        Schema::create('motivos_pausa', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 80)->unique();
            $table->foreignId('criado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
        });

        foreach ($this->motivosIniciais as $nome) {
            DB::table('motivos_pausa')->insert([
                'nome' => $nome,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('pausas_colaboradores', function (Blueprint $table) {
            $table->foreignId('motivo_pausa_id')->nullable()->after('usuario_id')->constrained('motivos_pausa')->nullOnDelete();
            $table->foreignId('iniciada_por_usuario_id')->nullable()->after('motivo_pausa_id')->constrained('usuarios')->nullOnDelete();
        });

        foreach ($this->motivosIniciais as $nome) {
            $motivoId = DB::table('motivos_pausa')->where('nome', $nome)->value('id');
            DB::table('pausas_colaboradores')->where('motivo', $nome)->update(['motivo_pausa_id' => $motivoId]);
        }

        DB::table('pausas_colaboradores')->whereNull('iniciada_por_usuario_id')->update([
            'iniciada_por_usuario_id' => DB::raw('usuario_id'),
        ]);
    }

    public function down(): void
    {
        Schema::table('pausas_colaboradores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('iniciada_por_usuario_id');
            $table->dropConstrainedForeignId('motivo_pausa_id');
        });

        Schema::dropIfExists('motivos_pausa');
    }
};
