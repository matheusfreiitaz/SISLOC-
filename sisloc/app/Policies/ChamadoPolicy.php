<?php

namespace App\Policies;

use App\Models\Chamado;
use App\Models\Usuario;

class ChamadoPolicy
{
    public function ver(Usuario $usuario, Chamado $chamado): bool
    {
        if (! $usuario->temPermissao('chamados.ver')) {
            return false;
        }

        return $usuario->temPermissao('chamados.ver_todos')
            || $chamado->solicitante_id === $usuario->id
            || $chamado->agente_id === $usuario->id
            || ($usuario->departamento_id && $usuario->departamento_id === $chamado->departamento_id);
    }

    public function editar(Usuario $usuario, Chamado $chamado): bool
    {
        return $usuario->temPermissao('chamados.editar') && $this->ver($usuario, $chamado);
    }

    public function responder(Usuario $usuario, Chamado $chamado): bool
    {
        return $usuario->temPermissao('chamados.responder') && $this->ver($usuario, $chamado);
    }

    public function atribuir(Usuario $usuario, Chamado $chamado): bool
    {
        return $usuario->temPermissao('chamados.atribuir') && $this->ver($usuario, $chamado);
    }

    public function transferir(Usuario $usuario, Chamado $chamado): bool
    {
        return $usuario->temPermissao('chamados.transferir') && $this->ver($usuario, $chamado);
    }

    public function finalizar(Usuario $usuario, Chamado $chamado): bool
    {
        return $usuario->temPermissao('chamados.finalizar') && $this->ver($usuario, $chamado);
    }

    /** Notas internas ficam ocultas para quem não atende chamados. */
    public function verNotasInternas(Usuario $usuario, Chamado $chamado): bool
    {
        return $usuario->temPermissao('chamados.atribuir') || $usuario->temPermissao('chamados.finalizar');
    }
}
