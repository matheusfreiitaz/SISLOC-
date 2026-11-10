<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacao extends Model
{
    protected $table = 'notificacoes';

    protected $fillable = ['usuario_id', 'tipo', 'titulo', 'mensagem', 'url', 'lida_em'];

    protected $casts = ['lida_em' => 'datetime'];
}
