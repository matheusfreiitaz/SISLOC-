<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotivoFinalizacao extends Model
{
    protected $table = 'motivos_finalizacao';

    protected $fillable = ['nome', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];
}
