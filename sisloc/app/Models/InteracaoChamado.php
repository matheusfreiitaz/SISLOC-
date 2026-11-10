<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InteracaoChamado extends Model
{
    protected $table = 'interacoes_chamado';

    protected $fillable = ['chamado_id', 'usuario_id', 'tipo', 'mensagem'];

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(Chamado::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(AnexoChamado::class, 'interacao_id');
    }
}
