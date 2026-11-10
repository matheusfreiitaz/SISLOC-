<?php

namespace Tests\Feature;

class PermissoesTest extends SislocTestCase
{
    public function test_solicitante_nao_acessa_area_administrativa_nem_por_rota_direta(): void
    {
        $solicitante = $this->usuario('solicitante');
        $this->actingAs($solicitante)->get('/admin/usuarios')->assertForbidden();
        $this->actingAs($solicitante)->get('/admin/perfis')->assertForbidden();
        $this->actingAs($solicitante)->get('/admin/auditoria')->assertForbidden();
        $this->actingAs($solicitante)->get('/admin/cadastros/departamentos')->assertForbidden();
    }

    public function test_administrador_acessa_a_area_administrativa(): void
    {
        $this->actingAs($this->usuario('administrador'))->get('/admin/usuarios')->assertOk();
    }

    public function test_solicitante_nao_ve_chamado_de_outra_pessoa(): void
    {
        $dono = $this->usuario('solicitante');
        $outro = $this->usuario('solicitante');
        $chamado = $this->chamado($dono);

        $this->actingAs($outro)->get("/chamados/{$chamado->id}")->assertForbidden();
        $this->actingAs($dono)->get("/chamados/{$chamado->id}")->assertOk();
    }

    public function test_perfil_sem_permissao_nao_atribui_chamado(): void
    {
        $solicitante = $this->usuario('solicitante');
        $chamado = $this->chamado($solicitante);
        $this->actingAs($solicitante)->post("/chamados/{$chamado->id}/atribuir", ['agente_id' => $solicitante->id])->assertForbidden();
    }
}
