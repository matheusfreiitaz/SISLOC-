<?php

namespace Database\Seeders;

use App\Models\Perfil;
use App\Models\Permissao;
use Illuminate\Database\Seeder;

class PermissaoSeeder extends Seeder
{
    public function run(): void
    {
        $permissoes = [
            'chamados.ver' => ['Chamados', 'Visualizar chamados permitidos'],
            'chamados.ver_todos' => ['Chamados', 'Visualizar chamados de todos os departamentos'],
            'chamados.criar' => ['Chamados', 'Abrir chamados'],
            'chamados.editar' => ['Chamados', 'Editar informações e prioridade'],
            'chamados.responder' => ['Chamados', 'Responder e comentar'],
            'chamados.atribuir' => ['Chamados', 'Atribuir responsável e alterar status'],
            'chamados.transferir' => ['Chamados', 'Transferir entre departamentos'],
            'chamados.finalizar' => ['Chamados', 'Finalizar atendimentos'],
            'finalizacoes.gerenciar' => ['Finalizações', 'Gerenciar motivos de finalização'],
            'usuarios.gerenciar' => ['Administração', 'Gerenciar usuários'],
            'perfis.gerenciar' => ['Administração', 'Gerenciar perfis e permissões'],
            'cadastros.gerenciar' => ['Administração', 'Gerenciar departamentos, categorias, prioridades e status'],
            'auditoria.ver' => ['Administração', 'Consultar auditoria'],
            'fila.ver' => ['Atendimento', 'Consultar a fila de atendimento'],
            'finalizados.ver' => ['Atendimento', 'Consultar atendimentos finalizados'],
            'finalizados.exportar' => ['Relatórios', 'Exportar atendimentos finalizados'],
            'formulario.configurar' => ['Administração', 'Configurar campos do formulário público'],
            'chamados.reabrir' => ['Chamados', 'Reabrir chamados finalizados'],
        ];

        foreach ($permissoes as $chave => [$modulo, $descricao]) {
            Permissao::updateOrCreate(['chave' => $chave], ['modulo' => $modulo, 'descricao' => $descricao]);
        }

        $perfis = [
            'administrador' => ['Administrador', 'Acesso total à plataforma.', array_keys($permissoes)],
            'supervisor' => ['Supervisor', 'Acompanha equipes e todos os chamados.', [
                'chamados.ver', 'chamados.ver_todos', 'chamados.criar', 'chamados.editar', 'chamados.responder',
                'chamados.atribuir', 'chamados.transferir', 'chamados.finalizar', 'auditoria.ver',
                'fila.ver', 'finalizados.ver', 'finalizados.exportar', 'chamados.reabrir',
            ]],
            'agente' => ['Agente de atendimento', 'Atende os chamados do seu departamento.', [
                'chamados.ver', 'chamados.criar', 'chamados.editar', 'chamados.responder',
                'chamados.atribuir', 'chamados.transferir', 'chamados.finalizar',
                'fila.ver', 'finalizados.ver',
            ]],
            'solicitante' => ['Usuário solicitante', 'Abre e acompanha os próprios chamados.', [
                'chamados.ver', 'chamados.criar', 'chamados.responder',
            ]],
        ];

        foreach ($perfis as $slug => [$nome, $descricao, $chaves]) {
            $perfil = Perfil::updateOrCreate(['slug' => $slug], ['nome' => $nome, 'descricao' => $descricao, 'sistema' => true]);
            $perfil->permissoes()->sync(Permissao::whereIn('chave', $chaves)->pluck('id'));
        }
    }
}
