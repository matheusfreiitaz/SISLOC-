<?php

namespace App\Services;

use App\Models\Notificacao;
use App\Models\Usuario;

class NotificacaoService
{
    /** Ponto único de envio: novos canais (e-mail, etc.) podem ser acoplados aqui. */
    public function notificar(?int $usuarioId, string $tipo, string $titulo, ?string $mensagem = null, ?string $url = null, ?int $ignorarUsuarioId = null): void
    {
        if (! $usuarioId || $usuarioId === $ignorarUsuarioId) {
            return;
        }

        Notificacao::create([
            'usuario_id' => $usuarioId,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'url' => $url,
        ]);
    }

    public function notificarDepartamento(int $departamentoId, string $tipo, string $titulo, ?string $mensagem, ?string $url, ?int $ignorarUsuarioId = null): void
    {
        Usuario::where('departamento_id', $departamentoId)->where('ativo', true)->pluck('id')
            ->each(fn ($id) => $this->notificar($id, $tipo, $titulo, $mensagem, $url, $ignorarUsuarioId));
    }
}
