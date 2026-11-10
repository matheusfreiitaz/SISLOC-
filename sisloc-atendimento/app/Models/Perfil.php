<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Perfil extends Model
{
    protected $table = 'perfis';

    protected $fillable = ['nome', 'slug', 'descricao', 'sistema'];

    protected $casts = ['sistema' => 'boolean'];

    public function permissoes(): BelongsToMany
    {
        return $this->belongsToMany(Permissao::class, 'perfil_permissao', 'perfil_id', 'permissao_id');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class);
    }
}
