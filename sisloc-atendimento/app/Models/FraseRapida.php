<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraseRapida extends Model
{
    protected $table = 'frases_rapidas';

    protected $fillable = ['usuario_id', 'titulo', 'conteudo'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
