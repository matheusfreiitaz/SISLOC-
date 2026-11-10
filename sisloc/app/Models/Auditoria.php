<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    protected $table = 'auditorias';

    public $timestamps = false;

    protected $fillable = ['usuario_id', 'acao', 'entidade', 'entidade_id', 'detalhes', 'ip'];

    protected $casts = ['detalhes' => 'array', 'created_at' => 'datetime'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
