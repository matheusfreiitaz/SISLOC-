<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prioridade extends Model
{
    protected $table = 'prioridades';

    protected $fillable = ['nome', 'cor', 'prazo_horas', 'nivel', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];
}
