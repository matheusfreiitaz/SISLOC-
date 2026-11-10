<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chamados', function (Blueprint $table) {
            $table->foreignId('solicitante_id')->nullable()->change();
            $table->string('solicitante_nome')->nullable();
            $table->string('solicitante_email')->nullable()->index();
            $table->string('empresa_cnpj', 14)->nullable()->index();
            $table->string('empresa_nome')->nullable()->index();
            $table->text('funcionamento_esperado')->nullable();
            $table->json('campos_publicos')->nullable();
        });

        Schema::table('interacoes_chamado', function (Blueprint $table) {
            $table->foreignId('usuario_id')->nullable()->change();
            $table->string('remetente_tipo', 16)->default('interno');
            $table->string('remetente_nome')->nullable();
            $table->timestamp('lida_por_cliente_em')->nullable();
            $table->timestamp('lida_por_equipe_em')->nullable();
        });

        Schema::table('anexos_chamado', function (Blueprint $table) {
            $table->foreignId('usuario_id')->nullable()->change();
        });

        Schema::create('campos_formulario_publico', function (Blueprint $table) {
            $table->id();
            $table->string('chave', 60)->unique();
            $table->string('rotulo');
            $table->text('descricao')->nullable();
            $table->string('tipo', 24);
            $table->boolean('obrigatorio')->default(false);
            $table->boolean('ativo')->default(true);
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->unsignedInteger('tamanho_maximo')->nullable();
            $table->json('opcoes')->nullable();
            $table->boolean('sistema')->default(false);
            $table->timestamps();
        });

        Schema::create('acessos_publicos_chamados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamado_id')->constrained('chamados')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expira_em');
            $table->timestamp('revogado_em')->nullable();
            $table->timestamp('ultimo_acesso_em')->nullable();
            $table->timestamps();
        });

        $campos = [
            ['cnpj', 'CNPJ da empresa', 'Informe o CNPJ da empresa solicitante.', 'identificacao', true, 10, 14],
            ['empresa_nome', 'Nome da empresa', 'Razão social ou nome pelo qual a empresa é conhecida.', 'texto', false, 20, 180],
            ['email', 'E-mail do solicitante', 'Enviaremos o protocolo e o link seguro da conversa para este e-mail.', 'email', true, 30, 254],
            ['nome', 'Nome completo', 'Nome da pessoa que está abrindo o chamado.', 'texto', true, 40, 180],
            ['categoria', 'Categoria do atendimento', 'Selecione a categoria que melhor descreve sua necessidade.', 'selecao', true, 50, null],
            ['relato', 'Relato do problema', 'Descreva o que está acontecendo, quais procedimentos foram realizados e em qual situação ocorre.', 'texto_longo', true, 60, 10000],
            ['esperado', 'Funcionamento esperado', 'Explique como o sistema deveria funcionar ou qual resultado você espera obter.', 'texto_longo', false, 70, 10000],
            ['anexos', 'Evidências e anexos', 'Anexe imagens, capturas de tela, PDFs ou documentos (até 10 MB por arquivo).', 'anexo', false, 80, 10240],
        ];

        foreach ($campos as [$chave, $rotulo, $descricao, $tipo, $obrigatorio, $ordem, $maximo]) {
            DB::table('campos_formulario_publico')->insert([
                'chave' => $chave,
                'rotulo' => $rotulo,
                'descricao' => $descricao,
                'tipo' => $tipo,
                'obrigatorio' => $obrigatorio,
                'ativo' => true,
                'ordem' => $ordem,
                'tamanho_maximo' => $maximo,
                'opcoes' => $chave === 'anexos' ? json_encode(['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'], JSON_THROW_ON_ERROR) : null,
                'sistema' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permissoes = [
            'fila.ver' => ['Atendimento', 'Consultar a fila de atendimento'],
            'finalizados.ver' => ['Atendimento', 'Consultar atendimentos finalizados'],
            'finalizados.exportar' => ['Relatórios', 'Exportar atendimentos finalizados'],
            'formulario.configurar' => ['Administração', 'Configurar campos do formulário público'],
            'chamados.reabrir' => ['Chamados', 'Reabrir chamados finalizados'],
        ];

        foreach ($permissoes as $chave => [$modulo, $descricao]) {
            DB::table('permissoes')->updateOrInsert(
                ['chave' => $chave],
                ['modulo' => $modulo, 'descricao' => $descricao, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $concessoes = [
            'administrador' => array_keys($permissoes),
            'supervisor' => ['fila.ver', 'finalizados.ver', 'finalizados.exportar', 'chamados.reabrir'],
            'agente' => ['fila.ver', 'finalizados.ver'],
        ];

        foreach ($concessoes as $slug => $chaves) {
            $perfilId = DB::table('perfis')->where('slug', $slug)->value('id');
            if (! $perfilId) {
                continue;
            }

            $ids = DB::table('permissoes')->whereIn('chave', $chaves)->pluck('id');
            foreach ($ids as $permissaoId) {
                DB::table('perfil_permissao')->updateOrInsert([
                    'perfil_id' => $perfilId,
                    'permissao_id' => $permissaoId,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('acessos_publicos_chamados');
        Schema::dropIfExists('campos_formulario_publico');

        Schema::table('anexos_chamado', fn (Blueprint $table) => $table->foreignId('usuario_id')->nullable(false)->change());
        Schema::table('interacoes_chamado', function (Blueprint $table) {
            $table->dropColumn(['remetente_tipo', 'remetente_nome', 'lida_por_cliente_em', 'lida_por_equipe_em']);
            $table->foreignId('usuario_id')->nullable(false)->change();
        });
        Schema::table('chamados', function (Blueprint $table) {
            $table->dropColumn(['solicitante_nome', 'solicitante_email', 'empresa_cnpj', 'empresa_nome', 'funcionamento_esperado', 'campos_publicos']);
            $table->foreignId('solicitante_id')->nullable(false)->change();
        });

        $chaves = ['fila.ver', 'finalizados.ver', 'finalizados.exportar', 'formulario.configurar', 'chamados.reabrir'];
        $ids = DB::table('permissoes')->whereIn('chave', $chaves)->pluck('id');
        DB::table('perfil_permissao')->whereIn('permissao_id', $ids)->delete();
        DB::table('permissoes')->whereIn('chave', $chaves)->delete();
    }
};
