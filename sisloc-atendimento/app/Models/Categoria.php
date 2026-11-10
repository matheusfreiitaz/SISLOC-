<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = ['nome', 'categoria_pai_id', 'departamento_id', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function pai(): BelongsTo
    {
        return $this->belongsTo(self::class, 'categoria_pai_id');
    }

    public function subcategorias(): HasMany
    {
        return $this->hasMany(self::class, 'categoria_pai_id');
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }
}
