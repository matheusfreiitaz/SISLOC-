<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InteracaoEdicao extends Model
{
    protected $table = 'interacoes_edicoes';

    protected $fillable = ['usuario_id', 'mensagem_anterior'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
