<?php

namespace Tests\Feature;

use App\Models\Departamento;
use App\Services\MonitoramentoChamadosService;
use Illuminate\Support\Facades\DB;

class MonitoramentoChamadosTest extends SislocTestCase
{
    public function test_monitoramento_usa_intervalo_padrao_de_vinte_minutos(): void
    {
        $servico = app(MonitoramentoChamadosService::class);
        $this->assertSame(20, $servico->configuracoes()['intervalo_minutos']);

        $primeira = $servico->executar(true);
        $this->assertNotNull($primeira);
        $this->assertNull($servico->executar());

        $this->travel(20)->minutes();
        $this->assertNotNull($servico->executar());
    }

    public function test_respeita_dias_de_funcionamento_configurados(): void
    {
        $servico = app(MonitoramentoChamadosService::class);
        $configuracoes = $servico->padroes();
        $configuracoes['dias_semana'] = [(now()->dayOfWeek + 1) % 7];
        $servico->salvarConfiguracoes($configuracoes);

        $this->assertNull($servico->executar());
        $this->assertDatabaseCount('monitoramento_execucoes', 0);
    }

    public function test_identifica_chamado_sem_responsavel_e_em_atraso_e_nao_repete_alertas_ativos(): void
    {
        $departamento = Departamento::first();
        $solicitante = $this->usuario('solicitante', $departamento->id);
        $supervisor = $this->usuario('supervisor', $departamento->id);
        $chamado = $this->chamado($solicitante, $departamento->id);
        DB::table('chamados')->where('id', $chamado->id)->update([
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
            'prazo_em' => now()->subMinute(),
        ]);

        $servico = app(MonitoramentoChamadosService::class);
        $primeira = $servico->executar(true);
        $this->assertSame(4, $primeira->ocorrencias_identificadas);
        $this->assertDatabaseHas('monitoramento_alertas', ['chamado_id' => $chamado->id, 'tipo' => 'sem_responsavel', 'resolvido_em' => null]);
        $this->assertDatabaseHas('monitoramento_alertas', ['chamado_id' => $chamado->id, 'tipo' => 'espera_fila', 'resolvido_em' => null]);
        $this->assertDatabaseHas('monitoramento_alertas', ['chamado_id' => $chamado->id, 'tipo' => 'sem_atualizacao', 'resolvido_em' => null]);
        $this->assertDatabaseHas('monitoramento_alertas', ['chamado_id' => $chamado->id, 'tipo' => 'sla_vencido', 'resolvido_em' => null]);

        $servico->executar(true);

        $this->assertSame(4, $supervisor->notificacoes()->count());
        $this->assertDatabaseCount('auditorias', 4);
        $this->assertDatabaseHas('monitoramento_execucoes', ['id' => $primeira->id, 'sucesso' => true, 'chamados_analisados' => 1]);
    }

    public function test_sla_proximo_gera_alerta_e_resolve_quando_deixa_de_se_aplicar(): void
    {
        $solicitante = $this->usuario('solicitante');
        $agente = $this->usuario('agente', $solicitante->departamento_id);
        $chamado = $this->chamado($solicitante, $solicitante->departamento_id);
        $chamado->update([
            'agente_id' => $agente->id,
            'prazo_em' => now()->addMinutes(30),
        ]);

        $servico = app(MonitoramentoChamadosService::class);
        $servico->executar(true);
        $this->assertDatabaseHas('monitoramento_alertas', ['chamado_id' => $chamado->id, 'tipo' => 'sla_proximo', 'resolvido_em' => null]);

        $chamado->update(['prazo_em' => now()->addHours(2)]);
        $servico->executar(true);
        $this->assertDatabaseMissing('monitoramento_alertas', ['chamado_id' => $chamado->id, 'tipo' => 'sla_proximo', 'resolvido_em' => null]);
    }

    public function test_configuracao_invalida_falha_explicitamente_no_comando(): void
    {
        DB::table('configuracoes')->insert([
            'chave' => 'monitoramento_chamados',
            'valor' => '{',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('atendimento:monitorar')
            ->expectsOutputToContain('Não foi possível concluir o monitoramento')
            ->assertFailed();

        $this->assertDatabaseHas('monitoramento_execucoes', ['sucesso' => false]);

        DB::table('configuracoes')->where('chave', 'monitoramento_chamados')->delete();
        $this->assertNotNull(app(MonitoramentoChamadosService::class)->executar(true));
        $this->assertDatabaseHas('monitoramento_execucoes', ['sucesso' => true]);
    }

    public function test_administrador_pode_consultar_e_atualizar_configuracao(): void
    {
        $administrador = $this->usuario('administrador', Departamento::first()->id);
        $this->actingAs($administrador)->get('/admin/monitoramento')
            ->assertOk()
            ->assertSee('Monitoramento automático')
            ->assertSee('Execuções recentes');

        $this->put('/admin/monitoramento', [
            'intervalo_minutos' => 15,
            'hora_inicio' => '08:00',
            'hora_fim' => '18:00',
            'dias_semana' => [1, 2, 3, 4, 5],
            'sem_atualizacao_minutos' => 90,
            'espera_fila_minutos' => 20,
            'pendente_minutos' => 600,
            'aguardando_usuario_minutos' => 720,
            'sla_proximo_minutos' => 45,
            'ativo' => '1',
            'notificar_supervisores' => '1',
        ])->assertRedirect()->assertSessionHas('sucesso');

        $this->assertSame(15, app(MonitoramentoChamadosService::class)->configuracoes()['intervalo_minutos']);
        $this->assertDatabaseHas('auditorias', ['acao' => 'monitoramento.configuracao_atualizada']);
    }
}
