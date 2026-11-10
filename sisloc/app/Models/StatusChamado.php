<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusChamado extends Model
{
    protected $table = 'status_chamado';

    protected $fillable = ['nome', 'slug', 'cor', 'final', 'ativo', 'ordem'];

    protected $casts = ['final' => 'boolean', 'ativo' => 'boolean'];

    public static function idPorSlug(string $slug): int
    {
        return (int) static::where('slug', $slug)->value('id');
    }
}
