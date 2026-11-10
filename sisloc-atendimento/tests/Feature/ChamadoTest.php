<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Chamado;
use App\Models\Departamento;
use App\Models\MotivoFinalizacao;
use App\Models\Notificacao;
use App\Models\Prioridade;
use App\Models\StatusChamado;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ChamadoTest extends SislocTestCase
{
    public function test_solicitante_abre_chamado_com_numero_e_prazo(): void
    {
        $usuario = $this->usuario('solicitante');
        $this->actingAs($usuario)->post('/chamados', [
            'assunto' => 'Sem acesso', 'descricao' => 'Não consigo entrar',
            'departamento_id' => Departamento::first()->id, 'prioridade_id' => Prioridade::first()->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('chamados', ['assunto' => 'Sem acesso', 'solicitante_id' => $usuario->id]);
        $chamado = Chamado::first();
        $this->assertNotNull($chamado->numero);
        $this->assertNotNull($chamado->prazo_em);
        $this->assertDatabaseHas('historico_chamado', ['chamado_id' => $chamado->id, 'campo' => 'chamado']);
    }

    public function test_agente_atribui_e_chamado_entra_em_atendimento(): void
    {
        $dep = Departamento::first();
        $agente = $this->usuario('agente', $dep->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $dep->id);

        $this->actingAs($agente)->post("/chamados/{$chamado->id}/atribuir", ['agente_id' => $agente->id])->assertRedirect();

        $chamado->refresh();
        $this->assertSame($agente->id, $chamado->agente_id);
        $this->assertSame(StatusChamado::idPorSlug('em_atendimento'), $chamado->status_id);
    }

    public function test_chamado_atribuido_nao_pode_ser_desatribuido_ou_reatribuido_sem_transferencia(): void
    {
        $departamento = Departamento::firstOrFail();
        $responsavel = $this->usuario('agente', $departamento->id);
        $outroAgente = $this->usuario('agente', $departamento->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $departamento->id);

        $this->actingAs($responsavel)->post("/chamados/{$chamado->id}/atribuir", [
            'agente_id' => $responsavel->id,
        ])->assertRedirect()->assertSessionHas('sucesso');

        $this->actingAs($responsavel)->get("/chamados/{$chamado->id}")
            ->assertOk()
            ->assertSee('Responsável atribuído')
            ->assertSee($responsavel->nome)
            ->assertSee('Para mudar o responsável, use o fluxo de transferência.')
            ->assertDontSee(route('chamados.atribuir', $chamado), false);

        $this->actingAs($responsavel)->post("/chamados/{$chamado->id}/atribuir", [
            'agente_id' => '',
        ])->assertSessionHasErrors('agente_id');

        $this->actingAs($responsavel)->post("/chamados/{$chamado->id}/atribuir", [
            'agente_id' => $outroAgente->id,
        ])->assertSessionHasErrors('agente_id');

        $this->assertSame($responsavel->id, $chamado->fresh()->agente_id);
    }

    public function test_atribuicao_exige_agente_do_departamento_atual(): void
    {
        $departamento = Departamento::firstOrFail();
        $outroDepartamento = Departamento::where('id', '!=', $departamento->id)->firstOrFail();
        $responsavel = $this->usuario('agente', $departamento->id);
        $agenteDeOutroDepartamento = $this->usuario('agente', $outroDepartamento->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $departamento->id);

        $this->actingAs($responsavel)->post("/chamados/{$chamado->id}/atribuir", [
            'agente_id' => $agenteDeOutroDepartamento->id,
        ])->assertSessionHasErrors('agente_id');

        $this->assertNull($chamado->fresh()->agente_id);
    }

    public function test_status_de_espera_exige_justificativa_e_registra_historico(): void
    {
        $departamento = Departamento::first();
        $agente = $this->usuario('agente', $departamento->id);
        $chamado = $this->chamado($this->usuario('solicitante', $departamento->id), $departamento->id);
        $status = StatusChamado::where('slug', 'aguardando_usuario')->firstOrFail();

        $this->actingAs($agente)->post("/chamados/{$chamado->id}/status", ['status_id' => $status->id])
            ->assertSessionHasErrors('justificativa');

        $this->actingAs($agente)->post("/chamados/{$chamado->id}/status", [
            'status_id' => $status->id,
            'justificativa' => 'Aguardando arquivo de log do solicitante.',
        ])->assertRedirect();

        $this->assertSame($status->id, $chamado->fresh()->status_id);
        $this->assertDatabaseHas('historico_chamado', [
            'chamado_id' => $chamado->id,
            'usuario_id' => $agente->id,
            'campo' => 'justificativa de status',
            'valor_novo' => 'Aguardando arquivo de log do solicitante.',
        ]);
    }

    public function test_nao_permite_mudar_status_diretamente_para_estado_final(): void
    {
        $departamento = Departamento::first();
        $agente = $this->usuario('agente', $departamento->id);
        $chamado = $this->chamado($this->usuario('solicitante', $departamento->id), $departamento->id);
        $statusFinal = StatusChamado::where('slug', 'encerrado')->firstOrFail();

        $this->actingAs($agente)->post("/chamados/{$chamado->id}/status", ['status_id' => $statusFinal->id])
            ->assertSessionHasErrors('status_id');

        $this->assertSame(StatusChamado::idPorSlug('aberto'), $chamado->fresh()->status_id);
    }

    public function test_transferencia_entre_departamentos_remove_responsavel(): void
    {
        [$origem, $destino] = [Departamento::first(), Departamento::skip(1)->first()];
        $agente = $this->usuario('agente', $origem->id);
        $supervisor = $this->usuario('supervisor', $origem->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $origem->id);
        $chamado->update(['agente_id' => $agente->id]);

        $this->actingAs($agente)->post("/chamados/{$chamado->id}/transferir", [
            'departamento_id' => $destino->id,
            'autorizador_id' => $supervisor->id,
            'prioridade_id' => Prioridade::where('nivel', 3)->firstOrFail()->id,
            'motivo' => 'Encaminhamento para equipe especializada',
        ])->assertRedirect();

        $chamado->refresh();
        $this->assertSame($destino->id, $chamado->departamento_id);
        $this->assertNull($chamado->agente_id);
        $this->assertSame(Prioridade::where('nivel', 3)->value('id'), $chamado->prioridade_id);
        $this->assertDatabaseHas('historico_chamado', [
            'chamado_id' => $chamado->id,
            'campo' => 'prioridade',
            'valor_novo' => 'Alta',
        ]);
        $this->assertDatabaseHas('historico_chamado', [
            'chamado_id' => $chamado->id,
            'campo' => 'autorizador informado',
            'valor_novo' => $supervisor->nome.' · Supervisor',
        ]);
        $this->assertDatabaseHas('notificacoes', [
            'usuario_id' => $supervisor->id,
            'tipo' => 'chamado.transferencia_informada',
            'titulo' => "Transferência do chamado {$chamado->numero} informada",
        ]);
        $this->assertStringContainsString('não exige aprovação', Notificacao::where('usuario_id', $supervisor->id)
            ->where('tipo', 'chamado.transferencia_informada')->value('mensagem'));
        $this->assertSame($supervisor->id, Auditoria::where('acao', 'chamado.transferido')
            ->where('entidade_id', $chamado->id)->value('detalhes')['autorizador_informado_id']);
        $this->assertDatabaseHas('auditorias', [
            'acao' => 'chamado.transferido',
            'entidade_id' => $chamado->id,
            'usuario_id' => $agente->id,
        ]);
    }

    public function test_transferidor_so_pode_finalizar_depois_que_o_chamado_voltar_atribuido_a_ele(): void
    {
        [$origem, $destino] = [Departamento::firstOrFail(), Departamento::skip(1)->firstOrFail()];
        $supervisor = $this->usuario('supervisor', $origem->id);
        $agenteDestino = $this->usuario('agente', $destino->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $origem->id);
        $chamado->update(['agente_id' => $supervisor->id]);
        $dadosTransferencia = [
            'prioridade_id' => Prioridade::firstOrFail()->id,
            'motivo' => 'Encaminhamento para equipe especializada',
        ];

        $this->actingAs($supervisor)->post("/chamados/{$chamado->id}/transferir", $dadosTransferencia + [
            'departamento_id' => $destino->id,
            'agente_id' => $agenteDestino->id,
            'autorizador_id' => $supervisor->id,
        ])->assertRedirect();

        $this->assertContains($supervisor->id, $chamado->fresh()->finalizacao_bloqueada_usuarios);
        $this->actingAs($supervisor)->get("/chamados/{$chamado->id}")
            ->assertOk()
            ->assertSee('Finalização temporariamente bloqueada')
            ->assertSee('disabled', false);

        $dadosFinalizacao = [
            'motivo_id' => MotivoFinalizacao::firstOrFail()->id,
            'descricao_solucao' => 'Solução registrada após retorno ao responsável.',
        ];
        $this->actingAs($supervisor)->post("/chamados/{$chamado->id}/finalizar", $dadosFinalizacao)
            ->assertSessionHasErrors('chamado');
        $this->assertDatabaseMissing('finalizacoes_chamado', ['chamado_id' => $chamado->id]);

        $this->actingAs($agenteDestino)->post("/chamados/{$chamado->id}/transferir", $dadosTransferencia + [
            'departamento_id' => $origem->id,
            'agente_id' => $supervisor->id,
            'autorizador_id' => $supervisor->id,
        ])->assertRedirect();

        $chamado->refresh();
        $this->assertSame($supervisor->id, $chamado->agente_id);
        $this->assertNotContains($supervisor->id, $chamado->finalizacao_bloqueada_usuarios);
        $this->assertContains($agenteDestino->id, $chamado->finalizacao_bloqueada_usuarios);

        $this->actingAs($supervisor)->post("/chamados/{$chamado->id}/finalizar", $dadosFinalizacao)
            ->assertRedirect();
        $this->assertDatabaseHas('finalizacoes_chamado', ['chamado_id' => $chamado->id]);
    }

    public function test_formulario_de_resposta_exibe_compositor_com_abas_e_barra_de_anexos(): void
    {
        $departamento = Departamento::firstOrFail();
        $agente = $this->usuario('agente', $departamento->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $departamento->id);

        $this->actingAs($agente)->get("/chamados/{$chamado->id}")
            ->assertOk()
            ->assertSee('Resposta')
            ->assertSee('Nota privada')
            ->assertSee('data-message-editor', false)
            ->assertSee('data-editor-command="bold"', false)
            ->assertSee('data-anexar-arquivo', false)
            ->assertSee('data-anexar-imagem', false)
            ->assertSee('data-abrir-frases-rapidas', false)
            ->assertSee('data-anexos-resposta', false);
    }

    public function test_frases_rapidas_sao_pessoais_e_podem_ser_editadas_e_excluidas(): void
    {
        $departamento = Departamento::firstOrFail();
        $agente = $this->usuario('agente', $departamento->id);
        $colega = $this->usuario('agente', $departamento->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $departamento->id);

        $this->actingAs($agente)->post("/chamados/{$chamado->id}/frases-rapidas", [
            'titulo' => 'Saudação inicial',
            'conteudo' => 'Olá! Vou analisar seu chamado agora.',
        ])->assertRedirect()->assertSessionHas('sucesso');

        $fraseId = \Illuminate\Support\Facades\DB::table('frases_rapidas')
            ->where('usuario_id', $agente->id)->value('id');
        $this->assertNotNull($fraseId);
        $this->actingAs($agente)->get("/chamados/{$chamado->id}")
            ->assertOk()->assertSee('Saudação inicial')->assertSee('data-frase-conteudo=', false);
        $this->actingAs($colega)->get("/chamados/{$chamado->id}")
            ->assertOk()->assertDontSee('Saudação inicial')->assertDontSee('Vou analisar seu chamado agora.');

        $this->actingAs($colega)->put("/chamados/{$chamado->id}/frases-rapidas/{$fraseId}", [
            'titulo' => 'Tentativa de alteração',
            'conteudo' => 'Texto de outro colaborador',
        ])->assertNotFound();

        $this->actingAs($agente)->put("/chamados/{$chamado->id}/frases-rapidas/{$fraseId}", [
            'titulo' => 'Saudação atualizada',
            'conteudo' => 'Olá! Vou analisar e retorno em breve.',
        ])->assertRedirect()->assertSessionHas('sucesso');
        $this->assertDatabaseHas('frases_rapidas', [
            'id' => $fraseId,
            'titulo' => 'Saudação atualizada',
            'conteudo' => 'Olá! Vou analisar e retorno em breve.',
        ]);

        $this->actingAs($agente)->delete("/chamados/{$chamado->id}/frases-rapidas/{$fraseId}")
            ->assertRedirect()->assertSessionHas('sucesso');
        $this->assertDatabaseMissing('frases_rapidas', ['id' => $fraseId]);
    }

    public function test_atendente_pode_corrigir_sua_mensagem_e_a_versao_anterior_fica_registrada(): void
    {
        $departamento = Departamento::firstOrFail();
        $agente = $this->usuario('agente', $departamento->id);
        $outroAgente = $this->usuario('agente', $departamento->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $departamento->id);
        $interacao = $chamado->interacoes()->create([
            'usuario_id' => $agente->id,
            'tipo' => 'resposta',
            'mensagem' => '<p>Texto com <strong>erro</strong>.</p>',
        ]);

        $this->actingAs($agente)->put("/chamados/{$chamado->id}/interacoes/{$interacao->id}", [
            'mensagem' => '<p>Texto <em>corrigido</em>.</p>',
        ])->assertRedirect()->assertSessionHas('sucesso');

        $this->assertDatabaseHas('interacoes_chamado', [
            'id' => $interacao->id,
            'mensagem' => '<p>Texto <em>corrigido</em>.</p>',
        ]);
        $this->assertDatabaseHas('interacoes_edicoes', [
            'interacao_id' => $interacao->id,
            'usuario_id' => $agente->id,
            'mensagem_anterior' => '<p>Texto com <strong>erro</strong>.</p>',
        ]);
        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $agente->id,
            'acao' => 'chamado.mensagem_corrigida',
            'entidade_id' => $chamado->id,
        ]);
        $this->actingAs($agente)->get("/chamados/{$chamado->id}")
            ->assertOk()
            ->assertSee('Mensagem editada. Texto anterior:', false)
            ->assertSee('Texto com erro.');

        $this->actingAs($agente)->get("/chamados/{$chamado->id}/mensagens?depois_de={$interacao->id}&atualizadas_depois=".urlencode(now()->subMinute()->toDateTimeString()))
            ->assertOk()
            ->assertJsonPath('mensagens.0.mensagem_html', '<p>Texto <em>corrigido</em>.</p>')
            ->assertJsonPath('mensagens.0.editada', true)
            ->assertJsonPath('mensagens.0.edicao_anterior', 'Texto com erro.');

        $this->actingAs($outroAgente)->put("/chamados/{$chamado->id}/interacoes/{$interacao->id}", [
            'mensagem' => 'Alteração de outro usuário',
        ])->assertForbidden();
    }

    public function test_sanitizador_de_mensagens_preserva_formatacao_e_remove_conteudo_perigoso(): void
    {
        $html = app(\App\Services\MensagemChamadoSanitizer::class)
            ->sanitizar('<p>Olá <strong>equipe</strong><script>alert(1)</script><a href="javascript:alert(1)">link</a></p>');

        $this->assertStringContainsString('<strong>equipe</strong>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_resposta_armazena_arquivo_anexado(): void
    {
        Storage::fake('local');
        $departamento = Departamento::firstOrFail();
        $agente = $this->usuario('agente', $departamento->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $departamento->id);

        $this->actingAs($agente)->post("/chamados/{$chamado->id}/responder", [
            'mensagem' => 'Segue o documento solicitado.',
            'tipo' => 'resposta',
            'anexos' => [UploadedFile::fake()->create('documento.pdf', 150, 'application/pdf')],
        ])->assertRedirect();

        $anexo = $chamado->anexos()->firstOrFail();
        $this->assertSame('documento.pdf', $anexo->nome_original);
        Storage::disk('local')->assertExists($anexo->caminho);
    }

    public function test_mais_detalhamentos_sao_salvos_com_historico_e_apenas_internos(): void
    {
        $departamento = Departamento::firstOrFail();
        $agente = $this->usuario('agente', $departamento->id);
        $solicitante = $this->usuario('solicitante', $departamento->id);
        $chamado = $this->chamado($solicitante, $departamento->id);
        $chamado->interacoes()->create([
            'usuario_id' => $solicitante->id,
            'remetente_tipo' => 'cliente',
            'remetente_nome' => $solicitante->nome,
            'mensagem' => 'O problema voltou depois da atualização.',
            'tipo' => 'resposta',
        ]);
        $dados = [
            'demandas_recorrentes' => 'Relata falhas frequentes ao entrar.',
            'tom_comunicacao_cliente' => 'preocupado',
        ];

        $this->actingAs($agente)->post("/chamados/{$chamado->id}/mais-detalhamentos", $dados)
            ->assertRedirect(route('chamados.detalhamentos', $chamado))->assertSessionHas('sucesso');
        $this->assertSame($dados['demandas_recorrentes'], $chamado->fresh()->demandas_recorrentes);
        $this->assertSame('preocupado', $chamado->fresh()->tom_comunicacao_cliente);
        $this->assertDatabaseHas('auditorias', [
            'acao' => 'chamado.detalhamentos_atualizados',
            'entidade_id' => $chamado->id,
            'usuario_id' => $agente->id,
        ]);
        $this->actingAs($agente)->get(route('chamados.detalhamentos', $chamado))
            ->assertOk()
            ->assertSee('Resumo automático da solicitação')
            ->assertSee('O problema voltou depois da atualização.')
            ->assertSee('Relata falhas frequentes ao entrar.');

        $this->actingAs($solicitante)->get("/chamados/{$chamado->id}")
            ->assertOk()->assertDontSee('Mais detalhamentos');
        $this->actingAs($solicitante)->get(route('chamados.detalhamentos', $chamado))->assertForbidden();
    }

    public function test_mais_detalhamentos_contam_e_exibem_historico_do_cliente(): void
    {
        $departamento = Departamento::firstOrFail();
        $agente = $this->usuario('agente', $departamento->id);
        $solicitante = $this->usuario('solicitante', $departamento->id);
        $anterior = $this->chamado($solicitante, $departamento->id);
        $anterior->update(['assunto' => 'Problema recorrente de acesso']);
        $atual = $this->chamado($solicitante, $departamento->id);

        $this->actingAs($agente)->get(route('chamados.detalhamentos', $atual))
            ->assertOk()
            ->assertSee('Mais detalhamentos')
            ->assertSee('Chamados neste mês')
            ->assertSee('Problema recorrente de acesso')
            ->assertSee('2</p>', false);
    }

    public function test_transferencia_exige_escolher_prioridade(): void
    {
        $origem = Departamento::firstOrFail();
        $destino = Departamento::skip(1)->firstOrFail();
        $agente = $this->usuario('agente', $origem->id);
        $supervisor = $this->usuario('supervisor', $origem->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $origem->id);

        $this->actingAs($agente)->post("/chamados/{$chamado->id}/transferir", [
            'departamento_id' => $destino->id,
            'autorizador_id' => $supervisor->id,
            'motivo' => 'Encaminhamento para equipe especializada',
        ])->assertSessionHasErrors('prioridade_id');

        $this->assertSame($origem->id, $chamado->fresh()->departamento_id);
    }

    public function test_transferencia_nao_aceita_agente_como_autorizador_informado(): void
    {
        $origem = Departamento::firstOrFail();
        $destino = Departamento::skip(1)->firstOrFail();
        $agente = $this->usuario('agente', $origem->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $origem->id);

        $this->actingAs($agente)->post("/chamados/{$chamado->id}/transferir", [
            'departamento_id' => $destino->id,
            'autorizador_id' => $agente->id,
            'prioridade_id' => Prioridade::firstOrFail()->id,
            'motivo' => 'Encaminhamento para equipe especializada',
        ])->assertSessionHasErrors('autorizador_id');

        $this->assertSame($origem->id, $chamado->fresh()->departamento_id);
    }

    public function test_resposta_do_agente_registra_primeira_resposta(): void
    {
        $dep = Departamento::first();
        $agente = $this->usuario('agente', $dep->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $dep->id);

        $this->actingAs($agente)->post("/chamados/{$chamado->id}/responder", ['mensagem' => 'Olá!', 'tipo' => 'resposta'])->assertRedirect();

        $this->assertNotNull($chamado->fresh()->primeira_resposta_em);
        $this->assertDatabaseHas('interacoes_chamado', ['chamado_id' => $chamado->id, 'mensagem' => 'Olá!']);
        $this->assertDatabaseHas('auditorias', ['acao' => 'chamado.mensagem_registrada', 'entidade_id' => $chamado->id]);
    }

    public function test_conversa_interna_tem_controle_para_voltar_as_mensagens_mais_recentes(): void
    {
        $departamento = Departamento::first();
        $agente = $this->usuario('agente', $departamento->id);
        $chamado = $this->chamado($this->usuario('solicitante', $departamento->id), $departamento->id);

        $this->actingAs($agente)->get("/chamados/{$chamado->id}")
            ->assertOk()
            ->assertSee('data-ir-ultimas-mensagens', false)
            ->assertSee('max-h-[65vh]', false);
    }

    public function test_finalizacao_exige_motivo_e_descricao_e_registra_dados(): void
    {
        $dep = Departamento::first();
        $agente = $this->usuario('agente', $dep->id);
        $solicitante = $this->usuario('solicitante', $dep->id);
        $chamado = $this->chamado($solicitante, $dep->id);
        $chamado->interacoes()->create([
            'usuario_id' => $solicitante->id,
            'remetente_tipo' => 'cliente',
            'remetente_nome' => $solicitante->nome,
            'mensagem' => 'O erro também aparece ao exportar o relatório.',
            'tipo' => 'resposta',
        ]);
        $chamado->update([
            'demandas_recorrentes' => 'Falhas recorrentes ao acessar relatórios.',
            'tom_comunicacao_cliente' => 'preocupado',
        ]);

        $this->actingAs($agente)->post("/chamados/{$chamado->id}/finalizar", [])->assertSessionHasErrors(['motivo_id', 'descricao_solucao']);

        $motivo = MotivoFinalizacao::first();
        $resposta = $this->actingAs($agente)->post("/chamados/{$chamado->id}/finalizar", [
            'motivo_id' => $motivo->id, 'descricao_solucao' => 'Senha redefinida.',
        ]);

        $chamado->refresh();
        $finalizacao = $chamado->finalizacao;
        $this->assertSame(StatusChamado::idPorSlug('resolvido'), $chamado->status_id);
        $this->assertNotNull($chamado->finalizado_em);
        $this->assertNotNull($finalizacao->relatorio);
        $this->assertStringContainsString('O erro também aparece ao exportar o relatório.', $finalizacao->relatorio['chamado']['resumo_automatico']);
        $this->assertSame('Falhas recorrentes ao acessar relatórios.', $finalizacao->relatorio['cliente']['demandas_recorrentes_informadas_pela_equipe']);
        $this->assertSame('preocupado', $finalizacao->relatorio['cliente']['comunicacao_observada_neste_atendimento']);
        $this->assertDatabaseHas('finalizacoes_chamado', ['chamado_id' => $chamado->id, 'usuario_id' => $agente->id, 'motivo_id' => $motivo->id]);
        $this->assertDatabaseHas('auditorias', ['acao' => 'chamado.finalizado', 'entidade_id' => $chamado->id]);
        $resposta->assertRedirect(route('chamados.relatorios.show', $finalizacao));
        $this->actingAs($agente)->get(route('chamados.relatorios.show', $finalizacao))
            ->assertOk()
            ->assertSee('Transcrição integral do atendimento')
            ->assertSee('O erro também aparece ao exportar o relatório.')
            ->assertSee('Senha redefinida.');
        $this->actingAs($solicitante)->get(route('chamados.relatorios.show', $finalizacao))->assertForbidden();
    }

    public function test_motivo_desativado_nao_pode_ser_usado(): void
    {
        $dep = Departamento::first();
        $agente = $this->usuario('agente', $dep->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $dep->id);
        $motivo = MotivoFinalizacao::first();
        $motivo->update(['ativo' => false]);

        $this->actingAs($agente)->post("/chamados/{$chamado->id}/finalizar", [
            'motivo_id' => $motivo->id, 'descricao_solucao' => 'x',
        ])->assertSessionHasErrors('motivo_id');
    }

    public function test_chamado_finalizado_nao_aceita_novas_respostas(): void
    {
        $dep = Departamento::first();
        $agente = $this->usuario('agente', $dep->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $dep->id);
        $chamado->update(['status_id' => StatusChamado::idPorSlug('resolvido')]);

        $this->actingAs($agente)->post("/chamados/{$chamado->id}/responder", ['mensagem' => 'Tarde demais', 'tipo' => 'resposta'])
            ->assertSessionHasErrors('chamado');
    }
}
