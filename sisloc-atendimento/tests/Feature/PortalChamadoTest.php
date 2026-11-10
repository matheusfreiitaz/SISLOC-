<?php

namespace Tests\Feature;

use App\Models\CampoFormularioPublico;
use App\Models\Categoria;
use App\Models\Chamado;
use App\Models\InteracaoChamado;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class PortalChamadoTest extends SislocTestCase
{
    public function test_cliente_abre_chamado_publico_e_recebe_link_individual(): void
    {
        Mail::fake();
        Storage::fake('local');
        $categoria = Categoria::whereNull('categoria_pai_id')->where('ativo', true)->firstOrFail();
        $this->usuario('agente', $categoria->departamento_id)->save();

        $this->get('/abrir-chamado')->assertOk()
            ->assertSee('ABRIR CHAMADO')
            ->assertSee('Abrir chamado')
            ->assertSee('chat-progresso-inline')
            ->assertSee('Ajuda e segurança');
        $response = $this->post('/abrir-chamado', ['campos' => [
            'cnpj' => '04.252.011/0001-10',
            'email' => 'cliente@empresa.test',
            'nome' => 'Pessoa Solicitante',
            'categoria' => $categoria->id,
            'relato' => 'O sistema apresenta erro ao gerar o relatório.',
            'esperado' => 'O relatório deveria ser gerado corretamente.',
        ], 'arquivos' => ['anexos' => [UploadedFile::fake()->createWithContent('evidencia.txt', 'Detalhes do erro')]]]);

        $response->assertRedirect(route('portal.chamados.confirmacao'))->assertSessionHas('protocolo');
        $chamado = Chamado::where('solicitante_email', 'cliente@empresa.test')->firstOrFail();
        $this->assertNull($chamado->solicitante_id);
        $this->assertSame('04252011000110', $chamado->empresa_cnpj);
        $this->assertNotEmpty($chamado->numero);
        $this->assertSame('O relatório deveria ser gerado corretamente.', $chamado->funcionamento_esperado);
        $anexo = $chamado->anexos()->firstOrFail();
        $this->assertNull($anexo->usuario_id);
        Storage::disk('local')->assertExists($anexo->caminho);
        $this->assertDatabaseHas('acessos_publicos_chamados', ['chamado_id' => $chamado->id]);
        $this->assertDatabaseHas('notificacoes', ['tipo' => 'chamado.novo']);

        $url = $this->app['session.store']->get('acompanhamento');
        $this->assertNotEmpty($url);
        $this->get(route('portal.chamados.confirmacao'))
            ->assertOk()
            ->assertSee('Solicitação registrada!')
            ->assertSee('Acompanhar meu chamado')
            ->assertSee('Copiar link seguro');
        $this->get(parse_url($url, PHP_URL_PATH))->assertOk()->assertSee($chamado->numero);
    }

    public function test_portal_rejeita_cnpj_invalido_e_campos_obrigatorios(): void
    {
        $categoria = Categoria::whereNull('categoria_pai_id')->firstOrFail();
        $this->from('/abrir-chamado')->post('/abrir-chamado', ['campos' => [
            'cnpj' => '11111111111111',
            'email' => 'nao-e-email',
            'nome' => 'Teste',
            'categoria' => $categoria->id,
            'relato' => '',
        ]])->assertRedirect('/abrir-chamado')->assertSessionHasErrors(['campos.cnpj', 'campos.email', 'campos.relato']);

        $this->assertDatabaseCount('chamados', 0);
    }

    public function test_token_publico_nao_exibe_notas_internas_e_so_abre_seu_chamado(): void
    {
        $categoria = Categoria::whereNull('categoria_pai_id')->firstOrFail();
        $this->post('/abrir-chamado', ['campos' => [
            'cnpj' => '04252011000110',
            'email' => 'cliente@empresa.test',
            'nome' => 'Pessoa Solicitante',
            'categoria' => $categoria->id,
            'relato' => 'Relato privado.',
        ]])->assertRedirect();
        $chamado = Chamado::firstOrFail();
        $url = $this->app['session.store']->get('acompanhamento');
        $path = parse_url($url, PHP_URL_PATH);
        $token = basename($path);

        $agente = $this->usuario('agente', $chamado->departamento_id);
        InteracaoChamado::create([
            'chamado_id' => $chamado->id,
            'usuario_id' => $agente->id,
            'tipo' => 'nota_interna',
            'mensagem' => 'SEGREDO INTERNO',
        ]);
        InteracaoChamado::create([
            'chamado_id' => $chamado->id,
            'usuario_id' => $agente->id,
            'tipo' => 'resposta',
            'mensagem' => 'Resposta visível ao cliente.',
        ]);

        $this->get($path)->assertOk()
            ->assertSee('Resposta visível ao cliente.')
            ->assertSee('rounded-bl-md border border-[#e8b36e] bg-gradient-to-br from-[#ffc77e] to-[#ffb45c] text-[#452600]', false)
            ->assertSee('data-ir-ultimas-mensagens', false)
            ->assertDontSee('SEGREDO INTERNO');
        $this->get("/atendimento/{$token}/mensagens")->assertOk()
            ->assertJsonFragment(['mensagem' => 'Resposta visível ao cliente.'])->assertDontSee('SEGREDO INTERNO');
        $this->get('/atendimento/'.str_repeat('a', 64))->assertNotFound();

        $this->post("/atendimento/{$token}/mensagens", ['mensagem' => 'Complemento do cliente.'])->assertRedirect();
        $this->assertDatabaseHas('interacoes_chamado', [
            'chamado_id' => $chamado->id,
            'remetente_tipo' => 'cliente',
            'mensagem' => 'Complemento do cliente.',
        ]);
        $this->get($path)
            ->assertSee('Complemento do cliente.')
            ->assertSee('rounded-br-md border border-[#e8d2ba] bg-gradient-to-br from-[#fffaf3] via-[#fbf1e5] to-[#f2e1ce] text-slate-800', false);
        $this->actingAs($agente)->post("/chamados/{$chamado->id}/acesso-publico/revogar")->assertRedirect();
        $this->get($path)->assertNotFound();
    }

    public function test_configuracao_do_formulario_respeita_permissao_e_campos_configurados(): void
    {
        $administrador = $this->usuario('administrador');
        $this->assertTrue($administrador->ehAdministrador());
        $this->assertTrue($administrador->ativo, json_encode($administrador->getAttributes()));
        $this->assertTrue($administrador->temPermissao('formulario.configurar'), json_encode($administrador->getAttributes()));
        $this->actingAs($administrador)->get('/admin/formulario-publico')->assertOk();
        $campoEsperado = CampoFormularioPublico::where('chave', 'esperado')->firstOrFail();
        $this->put("/admin/formulario-publico/campos/{$campoEsperado->id}", [
            'rotulo' => $campoEsperado->rotulo,
            'descricao' => $campoEsperado->descricao,
            'tipo' => $campoEsperado->tipo,
            'obrigatorio' => 0,
            'ativo' => 1,
            'ordem' => $campoEsperado->ordem,
            'tamanho_maximo' => $campoEsperado->tamanho_maximo,
            'opcoes_texto' => '',
        ])->assertRedirect();

        $solicitante = $this->usuario('solicitante');
        $this->actingAs($solicitante)->get('/admin/formulario-publico')->assertForbidden();

        $categoria = Categoria::whereNull('categoria_pai_id')->firstOrFail();
        $this->post('/abrir-chamado', ['campos' => [
            'cnpj' => '04252011000110',
            'email' => 'sem-esperado@empresa.test',
            'nome' => 'Solicitante',
            'categoria' => $categoria->id,
            'relato' => 'Relato sem comportamento esperado.',
        ]])->assertRedirect();

        $this->assertDatabaseHas('chamados', ['solicitante_email' => 'sem-esperado@empresa.test']);
    }
}
