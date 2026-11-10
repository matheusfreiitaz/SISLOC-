<?php

namespace Tests\Feature;

use App\Models\Departamento;
use App\Models\StatusChamado;

class PainelTest extends SislocTestCase
{
    public function test_painel_mostra_indicadores_diarios_e_tempos_medios_apenas_dos_chamados_visiveis(): void
    {
        $suporte = Departamento::where('nome', 'Suporte Técnico')->firstOrFail();
        $financeiro = Departamento::where('nome', 'Financeiro')->firstOrFail();
        $agente = $this->usuario('agente', $suporte->id);
        $solicitante = $this->usuario('solicitante');

        $atendido = $this->chamado($solicitante, $suporte->id);
        $atendido->forceFill([
            'assunto' => 'Atendimento do departamento',
            'status_id' => StatusChamado::idPorSlug('em_atendimento'),
            'created_at' => now()->subMinutes(50),
            'primeira_resposta_em' => now()->subMinutes(30),
        ])->save();
        $atendido->refresh();
        $this->assertSame(
            1200,
            $atendido->primeira_resposta_em->getTimestamp() - $atendido->created_at->getTimestamp(),
        );

        $concluido = $this->chamado($solicitante, $suporte->id);
        $concluido->forceFill([
            'assunto' => 'Concluído do departamento',
            'status_id' => StatusChamado::idPorSlug('resolvido'),
            'created_at' => now()->subHours(26),
            'finalizado_em' => now()->subHours(2),
        ])->save();

        $foraDoDepartamento = $this->chamado($solicitante, $financeiro->id);
        $foraDoDepartamento->update(['assunto' => 'Chamado fora do departamento']);

        $this->actingAs($agente)->get('/painel')
            ->assertOk()
            ->assertSee('Atendimentos recebidos hoje')
            ->assertSee('Atendimentos concluídos hoje')
            ->assertSee('1ª resposta média · 30 dias')
            ->assertSee('1ª resposta mediana · 30 dias')
            ->assertSee('20,0 min')
            ->assertSee('24,0 h')
            ->assertSee('SLA cumprido · 30 dias')
            ->assertSee('Idade da fila em aberto')
            ->assertSee('Distribuição por status')
            ->assertSee('Atendimentos nos últimos 30 dias')
            ->assertSee('Atendimento do departamento')
            ->assertDontSee('Chamado fora do departamento');
    }

    public function test_painel_com_indicadores_fica_disponivel_para_todos_os_niveis_de_funcionarios(): void
    {
        $departamento = Departamento::where('nome', 'Suporte Técnico')->firstOrFail();

        foreach (['agente', 'supervisor', 'administrador'] as $perfil) {
            $funcionario = $this->usuario($perfil, $departamento->id);

            $this->actingAs($funcionario)->get('/painel')
                ->assertOk()
                ->assertSee('Indicadores de produtividade')
                ->assertSee('Atendimentos nos últimos 30 dias');
        }
    }

    public function test_painel_mostra_desempenho_da_equipe_apenas_para_perfis_com_visao_global(): void
    {
        $departamento = Departamento::where('nome', 'Suporte Técnico')->firstOrFail();
        $agente = $this->usuario('agente', $departamento->id);
        $supervisor = $this->usuario('supervisor', $departamento->id);

        $this->actingAs($agente)->get('/painel')->assertOk()->assertDontSee('Desempenho por atendente · 30 dias');
        $this->actingAs($supervisor)->get('/painel')->assertOk()->assertSee('Desempenho por atendente · 30 dias');
    }
}
