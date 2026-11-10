<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampoFormularioPublico extends Model
{
    protected $table = 'campos_formulario_publico';

    protected $fillable = [
        'chave', 'rotulo', 'descricao', 'tipo', 'obrigatorio', 'ativo', 'ordem', 'tamanho_maximo', 'opcoes', 'sistema',
    ];

    protected $casts = [
        'obrigatorio' => 'boolean',
        'ativo' => 'boolean',
        'sistema' => 'boolean',
        'opcoes' => 'array',
    ];
}
