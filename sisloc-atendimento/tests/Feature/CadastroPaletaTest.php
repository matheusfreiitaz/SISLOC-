<?php

namespace Tests\Feature;

use App\Models\StatusChamado;

class CadastroPaletaTest extends SislocTestCase
{
    public function test_admin_escolhe_cor_de_paleta_predefinida_para_o_status(): void
    {
        $administrador = $this->usuario('administrador');

        $this->actingAs($administrador)->get('/admin/cadastros/status')
            ->assertOk()
            ->assertSee('type="radio"', false)
            ->assertSee('Turquesa')
            ->assertDontSee('Cor (hex)');

        $this->actingAs($administrador)->post('/admin/cadastros/status', [
            'nome' => 'Em validação visual',
            'cor' => '#14b8a6',
            'ordem' => 20,
            'final' => 0,
            'ativo' => 1,
        ])->assertRedirect('/admin/cadastros/status');

        $this->assertDatabaseHas('status_chamado', [
            'nome' => 'Em validação visual',
            'cor' => '#14b8a6',
        ]);
    }

    public function test_admin_nao_pode_gravar_cor_fora_da_paleta(): void
    {
        $administrador = $this->usuario('administrador');

        $this->actingAs($administrador)->post('/admin/cadastros/status', [
            'nome' => 'Cor inválida',
            'cor' => '#123456',
            'ordem' => 20,
            'final' => 0,
            'ativo' => 1,
        ])->assertSessionHasErrors('cor');

        $this->assertFalse(StatusChamado::where('nome', 'Cor inválida')->exists());
    }
}
