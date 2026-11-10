<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricoChamado extends Model
{
    protected $table = 'historico_chamado';

    public $timestamps = false;

    protected $fillable = ['chamado_id', 'usuario_id', 'campo', 'valor_anterior', 'valor_novo'];

    protected $casts = ['created_at' => 'datetime'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
