<?php

namespace Tests\Feature;

use App\Models\Departamento;
use App\Models\FinalizacaoChamado;
use App\Models\MotivoFinalizacao;
use App\Models\Prioridade;
use App\Models\StatusChamado;

class FilaEHistoricoTest extends SislocTestCase
{
    public function test_fila_exibe_chamados_ativos_e_e_restrita_a_colaboradores(): void
    {
        $departamento = Departamento::firstOrFail();
        $agente = $this->usuario('agente', $departamento->id);
        $ativo = $this->chamado($this->usuario('solicitante'), $departamento->id);
        $ativo->update(['numero' => '2026000001']);
        $finalizado = $this->chamado($this->usuario('solicitante'), $departamento->id);
        $finalizado->update(['numero' => '2026000002', 'status_id' => StatusChamado::idPorSlug('resolvido'), 'finalizado_em' => now()]);

        $this->actingAs($agente)->get('/fila')->assertOk()->assertSee('2026000001')->assertDontSee('2026000002');
        $this->actingAs($this->usuario('solicitante'))->get('/fila')->assertForbidden();
    }

    public function test_atendimento_finalizado_exibe_historico_e_restringe_clientes(): void
    {
        $departamento = Departamento::firstOrFail();
        $agente = $this->usuario('agente', $departamento->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $departamento->id);
        $chamado->update([
            'numero' => '2026000003',
            'agente_id' => $agente->id,
            'status_id' => StatusChamado::idPorSlug('resolvido'),
            'finalizado_em' => now(),
            'empresa_cnpj' => '04252011000110',
            'solicitante_nome' => 'Cliente Público',
        ]);
        FinalizacaoChamado::create([
            'chamado_id' => $chamado->id,
            'motivo_id' => MotivoFinalizacao::firstOrFail()->id,
            'usuario_id' => $agente->id,
            'descricao_solucao' => 'Ajuste realizado.',
        ]);

        $this->actingAs($agente)->get('/atendimentos-finalizados')->assertOk()
            ->assertSee('2026000003')->assertSee('Cliente Público')->assertSee('Ajuste realizado.');
        $supervisor = $this->usuario('supervisor', $departamento->id);
        $exportacao = $this->actingAs($supervisor)->get('/atendimentos-finalizados/exportar')->assertOk();
        $this->assertStringContainsString('2026000003', $exportacao->streamedContent());
        $this->actingAs($this->usuario('solicitante'))->get('/atendimentos-finalizados')->assertForbidden();
    }

    public function test_transferencia_registra_motivo_e_atribui_agente_do_departamento_destino(): void
    {
        [$origem, $destino] = [Departamento::firstOrFail(), Departamento::skip(1)->firstOrFail()];
        $agenteAtual = $this->usuario('agente', $origem->id);
        $novoAgente = $this->usuario('agente', $destino->id);
        $supervisor = $this->usuario('supervisor', $origem->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $origem->id);
        $chamado->update(['agente_id' => $agenteAtual->id]);

        $this->actingAs($agenteAtual)->post("/chamados/{$chamado->id}/transferir", [
            'departamento_id' => $destino->id,
            'agente_id' => $novoAgente->id,
            'autorizador_id' => $supervisor->id,
            'prioridade_id' => Prioridade::firstOrFail()->id,
            'motivo' => 'Especialização necessária',
            'observacoes' => 'Cliente aguarda retorno.',
        ])->assertRedirect();

        $chamado->refresh();
        $this->assertSame($destino->id, $chamado->departamento_id);
        $this->assertSame($novoAgente->id, $chamado->agente_id);
        $this->assertDatabaseHas('historico_chamado', [
            'chamado_id' => $chamado->id,
            'campo' => 'motivo da transferência',
            'valor_novo' => 'Especialização necessária',
        ]);
        $this->assertDatabaseHas('notificacoes', ['usuario_id' => $novoAgente->id, 'tipo' => 'chamado.transferido']);
        $this->assertDatabaseHas('notificacoes', ['usuario_id' => $supervisor->id, 'tipo' => 'chamado.transferencia_informada']);
        $this->assertDatabaseHas('auditorias', ['acao' => 'chamado.transferido', 'entidade_id' => $chamado->id]);
    }

    public function test_reabertura_preserva_registro_de_finalizacao_e_registra_historico(): void
    {
        $departamento = Departamento::firstOrFail();
        $supervisor = $this->usuario('supervisor', $departamento->id);
        $agente = $this->usuario('agente', $departamento->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $departamento->id);
        $motivo = MotivoFinalizacao::firstOrFail();
        FinalizacaoChamado::create([
            'chamado_id' => $chamado->id,
            'motivo_id' => $motivo->id,
            'usuario_id' => $agente->id,
            'descricao_solucao' => 'Registro original.',
        ]);
        $chamado->update(['status_id' => StatusChamado::idPorSlug('resolvido'), 'finalizado_em' => now()]);

        $this->actingAs($supervisor)->post("/chamados/{$chamado->id}/reabrir")->assertRedirect();

        $chamado->refresh();
        $this->assertSame(StatusChamado::idPorSlug('aberto'), $chamado->status_id);
        $this->assertNull($chamado->finalizado_em);
        $this->assertDatabaseHas('finalizacoes_chamado', ['chamado_id' => $chamado->id, 'descricao_solucao' => 'Registro original.']);
        $this->assertDatabaseHas('historico_chamado', ['chamado_id' => $chamado->id, 'campo' => 'reabertura']);
    }
}
