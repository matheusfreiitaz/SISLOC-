<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertaMonitoramento extends Model
{
    protected $table = 'monitoramento_alertas';

    protected $fillable = ['chamado_id', 'tipo', 'detectado_em', 'resolvido_em'];

    protected $casts = [
        'detectado_em' => 'datetime',
        'resolvido_em' => 'datetime',
    ];

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(Chamado::class);
    }
}
