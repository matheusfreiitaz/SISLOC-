<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnexoChamado extends Model
{
    protected $table = 'anexos_chamado';

    protected $fillable = ['chamado_id', 'interacao_id', 'usuario_id', 'nome_original', 'caminho', 'mime', 'tamanho'];

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(Chamado::class);
    }

    public function interacao(): BelongsTo
    {
        return $this->belongsTo(InteracaoChamado::class, 'interacao_id');
    }
}
