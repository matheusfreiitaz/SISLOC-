<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExecucaoMonitoramento extends Model
{
    protected $table = 'monitoramento_execucoes';

    protected $fillable = [
        'iniciada_em', 'concluida_em', 'chamados_analisados', 'ocorrencias_identificadas', 'sucesso', 'erro',
    ];

    protected $casts = [
        'iniciada_em' => 'datetime',
        'concluida_em' => 'datetime',
        'sucesso' => 'boolean',
    ];
}
