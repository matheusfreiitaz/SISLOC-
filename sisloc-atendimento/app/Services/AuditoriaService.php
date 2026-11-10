<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Support\Facades\Auth;

class AuditoriaService
{
    public function registrar(string $acao, ?string $entidade = null, ?int $entidadeId = null, array $detalhes = [], ?int $usuarioId = null): void
    {
        Auditoria::create([
            'usuario_id' => $usuarioId ?? Auth::id(),
            'acao' => $acao,
            'entidade' => $entidade,
            'entidade_id' => $entidadeId,
            'detalhes' => $detalhes ?: null,
            'ip' => request()?->ip(),
        ]);
    }
}
