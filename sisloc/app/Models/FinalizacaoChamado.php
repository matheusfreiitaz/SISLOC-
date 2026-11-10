<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalizacaoChamado extends Model
{
    protected $table = 'finalizacoes_chamado';

    protected $fillable = ['chamado_id', 'motivo_id', 'usuario_id', 'descricao_solucao', 'observacoes'];

    public function motivo(): BelongsTo
    {
        return $this->belongsTo(MotivoFinalizacao::class, 'motivo_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
