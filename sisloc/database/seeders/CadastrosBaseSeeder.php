<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Departamento;
use App\Models\MotivoFinalizacao;
use App\Models\Prioridade;
use App\Models\StatusChamado;
use Illuminate\Database\Seeder;

class CadastrosBaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Suporte Técnico', 'Financeiro', 'Comercial', 'Outros'] as $nome) {
            Departamento::firstOrCreate(['nome' => $nome]);
        }

        $prioridades = [['Baixa', '#22c55e', 72, 1], ['Média', '#eab308', 24, 2], ['Alta', '#f97316', 8, 3], ['Crítica', '#ef4444', 2, 4]];
        foreach ($prioridades as [$nome, $cor, $horas, $nivel]) {
            Prioridade::firstOrCreate(['nome' => $nome], ['cor' => $cor, 'prazo_horas' => $horas, 'nivel' => $nivel]);
        }

        $status = [
            ['Aberto', 'aberto', '#3b82f6', false], ['Em atendimento', 'em_atendimento', '#f97316', false],
            ['Aguardando usuário', 'aguardando_usuario', '#eab308', false], ['Aguardando terceiros', 'aguardando_terceiros', '#a855f7', false],
            ['Pendente', 'pendente', '#64748b', false], ['Resolvido', 'resolvido', '#22c55e', true],
            ['Encerrado', 'encerrado', '#475569', true], ['Cancelado', 'cancelado', '#ef4444', true],
        ];
        foreach ($status as $ordem => [$nome, $slug, $cor, $final]) {
            StatusChamado::firstOrCreate(['slug' => $slug], ['nome' => $nome, 'cor' => $cor, 'final' => $final, 'ordem' => $ordem + 1]);
        }

        foreach (['Problema solucionado', 'Orientação fornecida', 'Configuração realizada', 'Acesso restabelecido', 'Solicitação atendida',
            'Duplicidade de chamado', 'Solicitação cancelada', 'Encaminhado para outro setor', 'Sem retorno do solicitante'] as $motivo) {
            MotivoFinalizacao::firstOrCreate(['nome' => $motivo]);
        }

        $suporte = Departamento::where('nome', 'Suporte Técnico')->first();
        $estrutura = [
            'Acesso ao sistema' => ['Redefinição de senha', 'Erro de autenticação', 'Permissões de acesso'],
            'Configuração' => ['Instalação', 'Parâmetros do sistema'],
            'Dúvidas de uso' => ['Relatórios', 'Cadastros'],
        ];
        foreach ($estrutura as $pai => $filhas) {
            $categoria = Categoria::firstOrCreate(['nome' => $pai, 'categoria_pai_id' => null], ['departamento_id' => $suporte->id]);
            foreach ($filhas as $filha) {
                Categoria::firstOrCreate(['nome' => $filha, 'categoria_pai_id' => $categoria->id], ['departamento_id' => $suporte->id]);
            }
        }
    }
}
