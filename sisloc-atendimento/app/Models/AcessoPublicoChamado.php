<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcessoPublicoChamado extends Model
{
    protected $table = 'acessos_publicos_chamados';

    protected $fillable = ['chamado_id', 'token_hash', 'expira_em', 'revogado_em', 'ultimo_acesso_em'];

    protected $casts = [
        'expira_em' => 'datetime',
        'revogado_em' => 'datetime',
        'ultimo_acesso_em' => 'datetime',
    ];

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(Chamado::class);
    }
}
