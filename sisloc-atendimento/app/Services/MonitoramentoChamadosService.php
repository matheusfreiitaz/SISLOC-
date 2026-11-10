<?php

namespace App\Services;

use App\Models\AlertaMonitoramento;
use App\Models\Chamado;
use App\Models\ExecucaoMonitoramento;
use App\Models\Usuario;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use UnexpectedValueException;

class MonitoramentoChamadosService
{
    private const CHAVE_CONFIGURACAO = 'monitoramento_chamados';

    public function __construct(
        private NotificacaoService $notificacoes,
        private AuditoriaService $auditoria,
    ) {}

    public function configuracoes(): array
    {
        $valor = DB::table('configuracoes')->where('chave', self::CHAVE_CONFIGURACAO)->value('valor');
        $salvas = $valor === null ? [] : json_decode($valor, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($salvas)) {
            throw new UnexpectedValueException('As configurações de monitoramento devem ser um objeto JSON.');
        }

        return array_replace($this->padroes(), $salvas);
    }

    public function salvarConfiguracoes(array $dados): void
    {
        $existe = DB::table('configuracoes')->where('chave', self::CHAVE_CONFIGURACAO)->exists();
        $valores = ['valor' => json_encode($dados, JSON_THROW_ON_ERROR), 'updated_at' => now()];
        if (! $existe) {
            $valores['created_at'] = now();
        }

        DB::table('configuracoes')->updateOrInsert(
            ['chave' => self::CHAVE_CONFIGURACAO],
            $valores,
        );
    }

    public function padroes(): array
    {
        return [
            'ativo' => true,
            'intervalo_minutos' => 20,
            'hora_inicio' => '00:00',
            'hora_fim' => '23:59',
            'dias_semana' => range(0, 6),
            'sem_atualizacao_minutos' => 120,
            'espera_fila_minutos' => 30,
            'pendente_minutos' => 1440,
            'aguardando_usuario_minutos' => 1440,
            'sla_proximo_minutos' => 60,
            'notificar_supervisores' => true,
        ];
    }

    public function executar(bool $forcar = false): ?ExecucaoMonitoramento
    {
        $execucao = null;
        $tiposIdentificados = [];
        $analisados = 0;

        try {
            $configuracoes = $this->configuracoes();
            $agora = now();

            if (! $forcar && (! $configuracoes['ativo'] || ! $this->estaNoHorario($configuracoes, $agora)
                || ! $this->intervaloCumprido($configuracoes['intervalo_minutos'], $agora))) {
                return null;
            }

            $execucao = ExecucaoMonitoramento::create(['iniciada_em' => $agora]);
            Chamado::with(['status', 'prioridade', 'agente'])
                ->whereHas('status', fn ($consulta) => $consulta->where('final', false))
                ->chunkById(200, function ($chamados) use ($configuracoes, $execucao, &$tiposIdentificados, &$analisados) {
                    foreach ($chamados as $chamado) {
                        $analisados++;
                        $this->avaliarChamado($chamado, $configuracoes, $execucao, $tiposIdentificados);
                    }
                });

            AlertaMonitoramento::whereNull('resolvido_em')->chunkById(200, function ($alertas) use ($tiposIdentificados) {
                foreach ($alertas as $alerta) {
                    if (! isset($tiposIdentificados["{$alerta->chamado_id}:{$alerta->tipo}"])) {
                        $alerta->update(['resolvido_em' => now()]);
                    }
                }
            });

            $execucao->update([
                'concluida_em' => now(),
                'chamados_analisados' => $analisados,
                'ocorrencias_identificadas' => count($tiposIdentificados),
                'sucesso' => true,
            ]);

            return $execucao->refresh();
        } catch (Throwable $erro) {
            $execucao ??= ExecucaoMonitoramento::create(['iniciada_em' => now()]);
            $execucao->update([
                'concluida_em' => now(),
                'chamados_analisados' => $analisados,
                'ocorrencias_identificadas' => count($tiposIdentificados),
                'erro' => Str::limit($erro->getMessage(), 4000),
            ]);
            Log::error('Falha no monitoramento automático de chamados.', [
                'execucao_id' => $execucao->id,
                'exception' => $erro,
            ]);

            throw $erro;
        }
    }

    private function avaliarChamado(Chamado $chamado, array $configuracoes, ExecucaoMonitoramento $execucao, array &$identificadas): void
    {
        $agora = $execucao->iniciada_em;
        $status = $chamado->status->slug;
        $atualizadoEm = $chamado->updated_at;

        if (! $chamado->agente_id) {
            $this->registrarOcorrencia($chamado, 'sem_responsavel', 'Chamado sem responsável',
                'Este chamado ainda não possui agente responsável.', $configuracoes, $identificadas);
        }

        if ($status === 'aberto' && $chamado->created_at->lte($agora->copy()->subMinutes($configuracoes['espera_fila_minutos']))) {
            $this->registrarOcorrencia($chamado, 'espera_fila', 'Chamado aguardando atendimento',
                'O chamado ultrapassou o limite de espera configurado.', $configuracoes, $identificadas);
        }

        if ($status === 'pendente' && $atualizadoEm->lte($agora->copy()->subMinutes($configuracoes['pendente_minutos']))) {
            $this->registrarOcorrencia($chamado, 'pendente_atrasado', 'Chamado pendente além do limite',
                'O chamado está pendente há mais tempo que o limite configurado.', $configuracoes, $identificadas);
        }

        if ($status === 'aguardando_usuario'
            && $atualizadoEm->lte($agora->copy()->subMinutes($configuracoes['aguardando_usuario_minutos']))) {
            $this->registrarOcorrencia($chamado, 'aguardando_usuario', 'Chamado aguardando retorno do usuário',
                'O chamado aguarda resposta do solicitante além do limite configurado.', $configuracoes, $identificadas);
        }

        if ($atualizadoEm->lte($agora->copy()->subMinutes($configuracoes['sem_atualizacao_minutos']))) {
            $tipo = $status === 'em_atendimento' ? 'sem_movimentacao' : 'sem_atualizacao';
            $titulo = $status === 'em_atendimento' ? 'Atendimento sem movimentação' : 'Chamado sem atualização recente';
            $this->registrarOcorrencia($chamado, $tipo, $titulo,
                'O chamado está sem atualização há mais tempo que o limite configurado.', $configuracoes, $identificadas);
        }

        if ($chamado->prazo_em?->lt($agora)) {
            $this->registrarOcorrencia($chamado, 'sla_vencido', 'SLA vencido',
                'O prazo de atendimento deste chamado foi ultrapassado.', $configuracoes, $identificadas);
        } elseif ($chamado->prazo_em?->lte($agora->copy()->addMinutes($configuracoes['sla_proximo_minutos']))) {
            $this->registrarOcorrencia($chamado, 'sla_proximo', 'SLA próximo do vencimento',
                'O prazo de atendimento deste chamado está próximo do vencimento.', $configuracoes, $identificadas);
        }
    }

    private function registrarOcorrencia(
        Chamado $chamado,
        string $tipo,
        string $titulo,
        string $mensagem,
        array $configuracoes,
        array &$identificadas,
    ): void {
        $chave = "{$chamado->id}:{$tipo}";
        $identificadas[$chave] = true;
        $alerta = AlertaMonitoramento::firstOrNew(['chamado_id' => $chamado->id, 'tipo' => $tipo]);
        $novo = ! $alerta->exists || $alerta->resolvido_em !== null;
        $alerta->fill(['detectado_em' => now(), 'resolvido_em' => null])->save();

        if (! $novo) {
            return;
        }

        $url = route('chamados.show', $chamado);
        $this->notificacoes->notificar($chamado->agente_id, 'monitoramento.'.$tipo,
            "{$titulo}: {$chamado->numero}", $mensagem, $url);

        if ($configuracoes['notificar_supervisores']) {
            Usuario::where('ativo', true)
                ->where('departamento_id', $chamado->departamento_id)
                ->whereHas('perfil.permissoes', fn ($consulta) => $consulta->where('chave', 'chamados.ver_todos'))
                ->pluck('id')
                ->each(fn ($id) => $this->notificacoes->notificar((int) $id, 'monitoramento.'.$tipo,
                    "{$titulo}: {$chamado->numero}", $mensagem, $url, $chamado->agente_id));
        }

        $this->auditoria->registrar('monitoramento.alerta_identificado', 'chamado', $chamado->id, [
            'tipo' => $tipo,
            'titulo' => $titulo,
        ]);
    }

    private function intervaloCumprido(int $intervaloMinutos, CarbonInterface $agora): bool
    {
        $ultima = ExecucaoMonitoramento::whereNotNull('concluida_em')->latest('id')->first();

        return ! $ultima || $ultima->iniciada_em->addMinutes($intervaloMinutos)->lte($agora);
    }

    private function estaNoHorario(array $configuracoes, CarbonInterface $agora): bool
    {
        $inicio = $configuracoes['hora_inicio'];
        $fim = $configuracoes['hora_fim'];
        $horario = $agora->format('H:i');
        $dias = array_map('intval', $configuracoes['dias_semana']);

        if ($inicio === $fim) {
            return in_array($agora->dayOfWeek, $dias, true);
        }

        if ($inicio < $fim) {
            return in_array($agora->dayOfWeek, $dias, true) && $horario >= $inicio && $horario <= $fim;
        }

        $diaDeInicio = $horario >= $inicio ? $agora->dayOfWeek : ($agora->dayOfWeek + 6) % 7;

        return in_array($diaDeInicio, $dias, true) && ($horario >= $inicio || $horario <= $fim);
    }
}
