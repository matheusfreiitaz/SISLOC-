<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Chamado;
use App\Models\Departamento;
use App\Models\FinalizacaoChamado;
use App\Models\MotivoFinalizacao;
use App\Models\Perfil;
use App\Models\Prioridade;
use App\Models\StatusChamado;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class DemonstracaoSeeder extends Seeder
{
    public function run(): void
    {
        // Senha única de demonstração: Sisloc@2026 — todos os usuários são obrigados a trocá-la (ver README).
        $senha = 'Sisloc@2026';
        $suporte = Departamento::where('nome', 'Suporte Técnico')->first();
        $financeiro = Departamento::where('nome', 'Financeiro')->first();
        $perfil = fn (string $slug) => Perfil::where('slug', $slug)->value('id');

        $criar = fn (string $nome, string $email, string $slug, ?int $dep) => Usuario::firstOrCreate(
            ['email' => $email],
            ['nome' => $nome, 'password' => $senha, 'perfil_id' => $perfil($slug), 'departamento_id' => $dep, 'trocar_senha' => true]
        );

        $criar('Administrador SISLOC', 'admin@sisloc.test', 'administrador', null);
        $supervisores = [$criar('Carla Supervisora', 'supervisor@sisloc.test', 'supervisor', $suporte->id)];
        $agentes = [
            $criar('Rafael Agente', 'agente@sisloc.test', 'agente', $suporte->id),
            $criar('Bianca Agente', 'bianca@sisloc.test', 'agente', $suporte->id),
            $criar('Diego Financeiro', 'diego@sisloc.test', 'agente', $financeiro->id),
        ];
        $solicitantes = [
            $criar('Marina Locadora Alfa', 'marina@cliente.test', 'solicitante', null),
            $criar('Paulo Locadora Beta', 'paulo@cliente.test', 'solicitante', null),
            $criar('Helena Locadora Gama', 'helena@cliente.test', 'solicitante', null),
        ];

        if (Chamado::exists()) {
            return;
        }

        $assuntos = [
            'Não consigo acessar o sistema', 'Erro ao emitir relatório de contratos', 'Redefinir senha de usuário',
            'Dúvida sobre cadastro de equipamentos', 'Boleto emitido com valor incorreto', 'Instalação em nova estação',
            'Permissão negada no módulo financeiro', 'Lentidão ao abrir a agenda',
        ];
        $status = StatusChamado::pluck('id', 'slug');
        $prioridades = Prioridade::all();
        $categorias = Categoria::whereNotNull('categoria_pai_id')->get();
        $motivos = MotivoFinalizacao::where('ativo', true)->pluck('id');

        for ($i = 0; $i < 60; $i++) {
            $criado = now()->subDays(random_int(0, 45))->subHours(random_int(0, 23));
            $prioridade = $prioridades->random();
            $dep = random_int(1, 5) === 1 ? $financeiro : $suporte;
            $agente = random_int(1, 5) === 1 ? null : collect($agentes)->firstWhere('departamento_id', $dep->id);
            $slug = $agente ? collect(['em_atendimento', 'aguardando_usuario', 'resolvido', 'resolvido', 'encerrado'])->random() : 'aberto';
            $categoria = $categorias->random();
            $solicitante = $solicitantes[array_rand($solicitantes)];

            $chamado = Chamado::create([
                'solicitante_id' => $solicitante->id,
                'departamento_id' => $dep->id,
                'categoria_id' => $categoria->categoria_pai_id,
                'subcategoria_id' => $categoria->id,
                'assunto' => $assuntos[array_rand($assuntos)],
                'descricao' => 'Descrição do atendimento de demonstração registrada pelo solicitante.',
                'prioridade_id' => $prioridade->id,
                'status_id' => $status[$slug],
                'agente_id' => $agente?->id,
                'prazo_em' => $criado->copy()->addHours($prioridade->prazo_horas),
                'primeira_resposta_em' => $agente ? $criado->copy()->addMinutes(random_int(10, 600)) : null,
                'created_at' => $criado,
                'updated_at' => $criado,
            ]);
            $chamado->update(['numero' => sprintf('%s%06d', $criado->format('Y'), $chamado->id)]);

            if ($agente) {
                $chamado->interacoes()->create(['usuario_id' => $agente->id, 'tipo' => 'resposta',
                    'mensagem' => 'Olá! Estamos analisando a sua solicitação.', 'created_at' => $chamado->primeira_resposta_em]);
            }
            if (in_array($slug, ['resolvido', 'encerrado'], true) && $agente) {
                $fim = $criado->copy()->addHours(random_int(1, 60));
                $chamado->update(['finalizado_em' => $fim]);
                FinalizacaoChamado::create(['chamado_id' => $chamado->id, 'motivo_id' => $motivos->random(), 'usuario_id' => $agente->id,
                    'descricao_solucao' => 'Atendimento concluído conforme procedimento padrão.', 'created_at' => $fim, 'updated_at' => $fim]);
            }
        }
    }
}
