<?php

namespace Tests\Feature;

use App\Models\Chamado;
use App\Models\Departamento;
use App\Models\Perfil;
use App\Models\Prioridade;
use App\Models\StatusChamado;
use App\Models\Usuario;
use Database\Seeders\CadastrosBaseSeeder;
use Database\Seeders\PermissaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\Middleware\AuthenticateSession;
use Tests\TestCase;

abstract class SislocTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AuthenticateSession::class);
        $this->seed([PermissaoSeeder::class, CadastrosBaseSeeder::class]);
    }

    protected function usuario(string $perfil, ?int $departamentoId = null): Usuario
    {
        return Usuario::create([
            'nome' => ucfirst($perfil).' Teste', 'email' => $perfil.uniqid().'@teste.test', 'password' => 'Senha1234',
            'perfil_id' => Perfil::where('slug', $perfil)->value('id'), 'departamento_id' => $departamentoId,
        ]);
    }

    protected function chamado(Usuario $solicitante, ?int $departamentoId = null): Chamado
    {
        return Chamado::create([
            'solicitante_id' => $solicitante->id,
            'departamento_id' => $departamentoId ?? Departamento::first()->id,
            'assunto' => 'Assunto de teste', 'descricao' => 'Descrição de teste',
            'prioridade_id' => Prioridade::first()->id,
            'status_id' => StatusChamado::idPorSlug('aberto'),
            'prazo_em' => now()->addDay(),
        ]);
    }
}
