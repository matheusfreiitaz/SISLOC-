<?php

namespace Tests\Feature;

use App\Models\Departamento;
use App\Models\MotivoFinalizacao;
use App\Models\Prioridade;
use App\Models\StatusChamado;

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
        $chamado = \App\Models\Chamado::first();
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

    public function test_transferencia_entre_departamentos_remove_responsavel(): void
    {
        [$origem, $destino] = [Departamento::first(), Departamento::skip(1)->first()];
        $agente = $this->usuario('agente', $origem->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $origem->id);
        $chamado->update(['agente_id' => $agente->id]);

        $this->actingAs($agente)->post("/chamados/{$chamado->id}/transferir", ['departamento_id' => $destino->id])->assertRedirect();

        $chamado->refresh();
        $this->assertSame($destino->id, $chamado->departamento_id);
        $this->assertNull($chamado->agente_id);
    }

    public function test_resposta_do_agente_registra_primeira_resposta(): void
    {
        $dep = Departamento::first();
        $agente = $this->usuario('agente', $dep->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $dep->id);

        $this->actingAs($agente)->post("/chamados/{$chamado->id}/responder", ['mensagem' => 'Olá!', 'tipo' => 'resposta'])->assertRedirect();

        $this->assertNotNull($chamado->fresh()->primeira_resposta_em);
        $this->assertDatabaseHas('interacoes_chamado', ['chamado_id' => $chamado->id, 'mensagem' => 'Olá!']);
    }

    public function test_finalizacao_exige_motivo_e_descricao_e_registra_dados(): void
    {
        $dep = Departamento::first();
        $agente = $this->usuario('agente', $dep->id);
        $chamado = $this->chamado($this->usuario('solicitante'), $dep->id);

        $this->actingAs($agente)->post("/chamados/{$chamado->id}/finalizar", [])->assertSessionHasErrors(['motivo_id', 'descricao_solucao']);

        $motivo = MotivoFinalizacao::first();
        $this->actingAs($agente)->post("/chamados/{$chamado->id}/finalizar", [
            'motivo_id' => $motivo->id, 'descricao_solucao' => 'Senha redefinida.',
        ])->assertRedirect();

        $chamado->refresh();
        $this->assertSame(StatusChamado::idPorSlug('resolvido'), $chamado->status_id);
        $this->assertNotNull($chamado->finalizado_em);
        $this->assertDatabaseHas('finalizacoes_chamado', ['chamado_id' => $chamado->id, 'usuario_id' => $agente->id, 'motivo_id' => $motivo->id]);
        $this->assertDatabaseHas('auditorias', ['acao' => 'chamado.finalizado', 'entidade_id' => $chamado->id]);
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
