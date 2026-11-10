<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlertaMonitoramento;
use App\Models\ExecucaoMonitoramento;
use App\Services\AuditoriaService;
use App\Services\MonitoramentoChamadosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MonitoramentoController extends Controller
{
    public function index(MonitoramentoChamadosService $monitoramento)
    {
        return view('admin.monitoramento.index', [
            'configuracoes' => $monitoramento->configuracoes(),
            'execucoes' => ExecucaoMonitoramento::latest('iniciada_em')->limit(20)->get(),
            'alertasAtivos' => AlertaMonitoramento::whereNull('resolvido_em')->count(),
            'diasSemana' => [
                0 => 'Domingo',
                1 => 'Segunda-feira',
                2 => 'Terça-feira',
                3 => 'Quarta-feira',
                4 => 'Quinta-feira',
                5 => 'Sexta-feira',
                6 => 'Sábado',
            ],
        ]);
    }

    public function atualizar(Request $requisicao, MonitoramentoChamadosService $monitoramento, AuditoriaService $auditoria): RedirectResponse
    {
        $dados = $requisicao->validate([
            'intervalo_minutos' => ['required', 'integer', 'min:1', 'max:1440'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fim' => ['required', 'date_format:H:i'],
            'dias_semana' => ['required', 'array', 'min:1'],
            'dias_semana.*' => ['required', 'integer', 'between:0,6'],
            'sem_atualizacao_minutos' => ['required', 'integer', 'min:1', 'max:10080'],
            'espera_fila_minutos' => ['required', 'integer', 'min:1', 'max:10080'],
            'pendente_minutos' => ['required', 'integer', 'min:1', 'max:10080'],
            'aguardando_usuario_minutos' => ['required', 'integer', 'min:1', 'max:10080'],
            'sla_proximo_minutos' => ['required', 'integer', 'min:1', 'max:10080'],
        ]);
        $dados['dias_semana'] = array_values(array_unique(array_map('intval', $dados['dias_semana'])));
        $dados['ativo'] = $requisicao->boolean('ativo');
        $dados['notificar_supervisores'] = $requisicao->boolean('notificar_supervisores');

        $monitoramento->salvarConfiguracoes($dados);
        $auditoria->registrar('monitoramento.configuracao_atualizada', 'configuracao', null, $dados);

        return back()->with('sucesso', 'Configurações de monitoramento atualizadas.');
    }
}
