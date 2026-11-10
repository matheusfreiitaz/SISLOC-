<?php

namespace Tests\Feature;

use App\Models\Departamento;
use App\Models\Notificacao;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class PausaColaboradorTest extends SislocTestCase
{
    public function test_limite_simultaneo_bloqueia_nova_pausa_e_mostra_os_proximos_retornos(): void
    {
        DB::table('configuracoes_pausa')->where('id', 1)->update([
            'limite_simultaneas' => 2,
            'duracao_minutos' => 30,
        ]);
        $departamento = Departamento::firstOrFail();
        $primeiro = $this->usuario('agente', $departamento->id);
        $segundo = $this->usuario('agente', $departamento->id);
        $terceiro = $this->usuario('agente', $departamento->id);
        $primeiro->update(['nome' => 'Primeiro a retornar']);
        $segundo->update(['nome' => 'Segundo a retornar']);

        $this->actingAs($primeiro)->post('/pausas/iniciar', ['motivo' => 'Banheiro'])->assertRedirect()->assertSessionHas('sucesso');
        $this->actingAs($segundo)->post('/pausas/iniciar', ['motivo' => 'Almoço'])->assertRedirect()->assertSessionHas('sucesso');

        $this->actingAs($terceiro)->from('/pausas')
            ->post('/pausas/iniciar', ['motivo' => 'Feedback'])
            ->assertRedirect();

        $this->assertDatabaseCount('pausas_colaboradores', 2);
        $respostaTerceiro = $this->get('/pausas');
        $respostaTerceiro
            ->assertOk()
            ->assertSee($primeiro->nome)
            ->assertSee($segundo->nome)
            ->assertSee($primeiro->nome.' tem o retorno previsto mais próximo')
            ->assertDontSee('Banheiro · '.$departamento->nome, false)
            ->assertSee('Próximos retornos');
        $this->assertSame(0, substr_count($respostaTerceiro->getContent(), ' data-pausa-cronometro='));

        $this->actingAs($primeiro)->post('/pausas/retornar')->assertRedirect()->assertSessionHas('sucesso');
        $this->actingAs($terceiro)->post('/pausas/iniciar', ['motivo' => 'Feedback'])->assertRedirect()->assertSessionHas('sucesso');

        $this->assertDatabaseHas('pausas_colaboradores', [
            'usuario_id' => $primeiro->id,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $primeiro->id,
            'acao' => 'colaborador.pausa_iniciada',
        ]);
        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $primeiro->id,
            'acao' => 'colaborador.pausa_encerrada',
        ]);
        $this->assertDatabaseHas('pausas_colaboradores', [
            'usuario_id' => $primeiro->id,
            'motivo_pausa_id' => DB::table('motivos_pausa')->where('nome', 'Banheiro')->value('id'),
        ]);
    }

    public function test_administrador_configura_limite_e_duracao_da_pausa(): void
    {
        $administrador = $this->usuario('administrador');

        $this->actingAs($administrador)->get('/pausas')->assertOk()
            ->assertSee('Limites de pausa · administração');

        $this->actingAs($administrador)->put('/admin/pausas', [
            'limite_simultaneas' => 7,
            'duracao_minutos' => 20,
        ])->assertRedirect()->assertSessionHas('sucesso');

        $this->assertDatabaseHas('configuracoes_pausa', [
            'id' => 1,
            'limite_simultaneas' => 7,
            'duracao_minutos' => 20,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $administrador->id,
            'acao' => 'colaborador.pausas_configuradas',
        ]);

        $this->actingAs($administrador)->put('/admin/pausas', [
            'limite_simultaneas' => 1,
            'sem_limite' => 1,
        ])->assertRedirect()->assertSessionHas('sucesso');

        $this->assertDatabaseHas('configuracoes_pausa', [
            'id' => 1,
            'duracao_minutos' => 0,
        ]);
        $colaborador = $this->usuario('agente', Departamento::firstOrFail()->id);
        $this->actingAs($colaborador)->post('/pausas/iniciar', ['motivo' => 'Banheiro'])
            ->assertRedirect()->assertSessionHas('sucesso');
        $this->assertDatabaseHas('pausas_colaboradores', [
            'usuario_id' => $colaborador->id,
            'prevista_ate' => null,
        ]);
        $this->actingAs($colaborador)->get('/pausas')
            ->assertOk()
            ->assertSee('Sem limite')
            ->assertSee('Sem horário previsto para retorno.');

        $outroColaborador = $this->usuario('agente', Departamento::firstOrFail()->id);
        $this->actingAs($outroColaborador)->from('/pausas')->followingRedirects()
            ->post('/pausas/iniciar', ['motivo' => 'Almoço'])
            ->assertOk()
            ->assertSee($colaborador->nome.' está em pausa sem limite de tempo; não há previsão de retorno.');
    }

    public function test_administrador_cria_categoria_e_forca_pausa_de_um_colaborador(): void
    {
        DB::table('configuracoes_pausa')->where('id', 1)->update(['limite_simultaneas' => 1]);
        $departamento = Departamento::firstOrFail();
        $colaboradorEmPausa = $this->usuario('agente', $departamento->id);
        $colaboradorAlvo = $this->usuario('agente', $departamento->id);
        $administrador = $this->usuario('administrador');

        $this->actingAs($colaboradorEmPausa)->post('/pausas/iniciar', ['motivo' => 'Almoço'])
            ->assertRedirect()->assertSessionHas('sucesso');

        $this->actingAs($colaboradorAlvo)->post('/pausas/forcar', [
            'usuario_id' => $colaboradorEmPausa->id,
            'motivo' => 'Almoço',
        ])->assertForbidden();
        $this->actingAs($administrador)->post('/pausas/motivos', ['nome' => 'Treinamento especial'])
            ->assertRedirect()->assertSessionHas('sucesso');

        $this->actingAs($administrador)->post('/pausas/forcar', [
            'usuario_id' => $colaboradorAlvo->id,
            'motivo' => 'Treinamento especial',
        ])->assertRedirect()->assertSessionHas('sucesso');

        $this->assertDatabaseCount('pausas_colaboradores', 2);
        $this->assertDatabaseHas('pausas_colaboradores', [
            'usuario_id' => $colaboradorAlvo->id,
            'motivo_pausa_id' => DB::table('motivos_pausa')->where('nome', 'Treinamento especial')->value('id'),
            'iniciada_por_usuario_id' => $administrador->id,
            'retornada_em' => null,
        ]);
        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $administrador->id,
            'acao' => 'colaborador.pausa_forcada',
        ]);

        $this->actingAs($colaboradorAlvo)->get('/pausas')
            ->assertOk()
            ->assertSee('Treinamento especial')
            ->assertSee('Meu histórico de pausas');

        $this->actingAs($administrador)->get('/pausas')
            ->assertOk()
            ->assertSee('Treinamento especial')
            ->assertSee('Iniciado por')
            ->assertSee('Encerrar pausa');

        $this->actingAs($administrador)->post('/pausas/forcar', [
            'usuario_id' => $colaboradorAlvo->id,
            'motivo' => 'Treinamento especial',
        ])->assertRedirect()->assertSessionHasErrors('pausa');
        $this->assertDatabaseCount('pausas_colaboradores', 2);

        $pausaAtivaId = DB::table('pausas_colaboradores')
            ->where('usuario_id', $colaboradorAlvo->id)
            ->whereNull('retornada_em')
            ->value('id');
        $this->actingAs($colaboradorAlvo)->post("/pausas/{$pausaAtivaId}/encerrar-admin")->assertForbidden();
        $this->actingAs($administrador)->post("/pausas/{$pausaAtivaId}/encerrar-admin")
            ->assertRedirect()->assertSessionHas('sucesso');
        $this->assertDatabaseHas('pausas_colaboradores', [
            'id' => $pausaAtivaId,
            'retornada_em' => now()->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $administrador->id,
            'acao' => 'colaborador.pausa_encerrada_admin',
            'entidade_id' => $pausaAtivaId,
        ]);
    }

    public function test_auditoria_exibe_contagem_de_notificacoes_e_logs_dos_colaboradores(): void
    {
        $administrador = $this->usuario('administrador');
        $colaborador = $this->usuario('agente', Departamento::firstOrFail()->id);
        Notificacao::create([
            'usuario_id' => $colaborador->id,
            'tipo' => 'monitoramento.sla_vencido',
            'titulo' => 'Atenção ao prazo',
        ]);
        Notificacao::create([
            'usuario_id' => $colaborador->id,
            'tipo' => 'chamado.atribuido',
            'titulo' => 'Chamado atribuído',
            'lida_em' => now(),
        ]);
        DB::table('auditorias')->insert([
            'usuario_id' => $colaborador->id,
            'acao' => 'colaborador.pausa_iniciada',
            'entidade' => 'pausa_colaborador',
            'entidade_id' => 1,
            'created_at' => now(),
        ]);

        $this->actingAs($administrador)->get('/admin/auditoria?usuario_id='.$colaborador->id)
            ->assertOk()
            ->assertSee('Notificações recebidas por colaborador')
            ->assertSee('Alertas de atenção')
            ->assertSee('colaborador.pausa_iniciada')
            ->assertSee($colaborador->nome)
            ->assertSee('2');
    }

    public function test_colaborador_sem_permissao_de_fila_nao_pode_iniciar_pausa(): void
    {
        $solicitante = $this->usuario('solicitante');

        $this->actingAs($solicitante)->get('/pausas')->assertForbidden();
        $this->actingAs($solicitante)->post('/pausas/iniciar')->assertForbidden();
    }

    public function test_pausa_exibe_cronometro_privado_e_detalhes_para_supervisor_e_gestor(): void
    {
        $departamento = Departamento::firstOrFail();
        $pausado = $this->usuario('agente', $departamento->id);
        $colega = $this->usuario('agente', $departamento->id);
        $supervisor = $this->usuario('supervisor', $departamento->id);
        $gestorPerfil = Perfil::create([
            'nome' => 'Gestor',
            'slug' => 'gestor',
            'descricao' => 'Gestor de equipe',
            'sistema' => false,
        ]);
        $gestorPerfil->permissoes()->attach(Permissao::where('chave', 'fila.ver')->value('id'));
        $gestor = Usuario::create([
            'nome' => 'Gestor Teste',
            'email' => 'gestor'.uniqid().'@teste.test',
            'password' => 'Senha1234',
            'perfil_id' => $gestorPerfil->id,
            'departamento_id' => $departamento->id,
        ]);

        $this->actingAs($pausado)->post('/pausas/iniciar', ['motivo' => 'Café da manhã'])
            ->assertRedirect()->assertSessionHas('sucesso');

        $this->actingAs($pausado)->get('/pausas')
            ->assertOk()
            ->assertSee('Café da manhã')
            ->assertSee('Tempo decorrido')
            ->assertSee(' data-pausa-cronometro=', false);

        $respostaColega = $this->actingAs($colega)->get('/pausas');
        $respostaColega
            ->assertOk()
            ->assertSee($pausado->nome)
            ->assertDontSee('Café da manhã · '.$departamento->nome, false)
            ->assertDontSee('retorno previsto · atrasado');
        $this->assertSame(0, substr_count($respostaColega->getContent(), ' data-pausa-cronometro='));

        foreach ([$supervisor, $gestor] as $gestorOuSupervisor) {
            $this->actingAs($gestorOuSupervisor)->get('/pausas')
                ->assertOk()
                ->assertSee('Café da manhã')
                ->assertSee(' data-pausa-cronometro=', false)
                ->assertSee('decorridos');
        }
    }

    public function test_historico_de_pausas_e_pessoal_exceto_para_usuarios_com_acesso_administrativo(): void
    {
        $departamento = Departamento::firstOrFail();
        $colaborador = $this->usuario('agente', $departamento->id);
        $colega = $this->usuario('agente', $departamento->id);
        $supervisor = $this->usuario('supervisor', $departamento->id);
        $administrador = $this->usuario('administrador');
        $colaborador->update(['nome' => 'Colaborador Um']);
        $colega->update(['nome' => 'Colaborador Dois']);
        $supervisor->update(['nome' => 'Supervisor Historico']);

        foreach ([
            [$colaborador, 'Banheiro'],
            [$colega, 'Feedback'],
            [$supervisor, 'Reunião'],
        ] as [$usuario, $motivo]) {
            $this->actingAs($usuario)->post('/pausas/iniciar', ['motivo' => $motivo])
                ->assertRedirect()->assertSessionHas('sucesso');
            $this->actingAs($usuario)->post('/pausas/retornar')
                ->assertRedirect()->assertSessionHas('sucesso');
        }

        $respostaColaborador = $this->actingAs($colaborador)->get('/pausas');
        $respostaColaborador
            ->assertOk()
            ->assertSee('Meu histórico de pausas')
            ->assertSee(now()->format('d/m/Y'))
            ->assertDontSee($colega->nome)
            ->assertDontSee($supervisor->nome);

        $respostaSupervisor = $this->actingAs($supervisor)->get('/pausas');
        $respostaSupervisor
            ->assertOk()
            ->assertSee('Meu histórico de pausas')
            ->assertDontSee($colaborador->nome)
            ->assertDontSee($colega->nome);

        $this->actingAs($administrador)->get('/pausas')
            ->assertOk()
            ->assertSee('Histórico de pausas da equipe')
            ->assertSee($colaborador->nome)
            ->assertSee($colega->nome)
            ->assertSee($supervisor->nome);
    }

    public function test_motivo_da_pausa_e_obrigatorio_e_limitado_as_opcoes_predefinidas(): void
    {
        $agente = $this->usuario('agente', Departamento::firstOrFail()->id);

        $this->actingAs($agente)->post('/pausas/iniciar')->assertSessionHasErrors('motivo');
        $this->actingAs($agente)->post('/pausas/iniciar', ['motivo' => 'Outro'])
            ->assertSessionHasErrors('motivo');
        $this->assertDatabaseCount('pausas_colaboradores', 0);
    }
}
