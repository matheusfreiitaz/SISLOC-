<?php

namespace Tests\Feature;

class AutenticacaoTest extends SislocTestCase
{
    public function test_login_com_credenciais_validas_redireciona_ao_painel(): void
    {
        $usuario = $this->usuario('agente');
        $this->post('/entrar', ['email' => $usuario->email, 'password' => 'Senha1234'])->assertRedirect('/painel');
        $this->assertAuthenticatedAs($usuario);
        $this->assertDatabaseHas('auditorias', ['acao' => 'autenticacao.login', 'usuario_id' => $usuario->id]);
    }

    public function test_conta_desativada_nao_consegue_entrar(): void
    {
        $usuario = $this->usuario('agente');
        $usuario->update(['ativo' => false]);
        $this->post('/entrar', ['email' => $usuario->email, 'password' => 'Senha1234'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_visitante_e_redirecionado_ao_login(): void
    {
        $this->get('/chamados')->assertRedirect('/entrar');
    }

    public function test_tema_escolhido_e_salvo_para_o_usuario(): void
    {
        $usuario = $this->usuario('agente');
        $this->actingAs($usuario)->postJson('/conta/tema', ['tema' => 'escuro'])->assertOk();
        $this->assertSame('escuro', $usuario->fresh()->tema);
    }
}
