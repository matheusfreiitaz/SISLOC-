<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departamento extends Model
{
    protected $table = 'departamentos';

    protected $fillable = ['nome', 'descricao', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function agentes(): HasMany
    {
        return $this->hasMany(Usuario::class);
    }

    public function chamados(): HasMany
    {
        return $this->hasMany(Chamado::class);
    }
}
