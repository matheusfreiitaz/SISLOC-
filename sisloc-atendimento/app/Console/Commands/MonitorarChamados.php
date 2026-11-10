<?php

namespace App\Console\Commands;

use App\Services\MonitoramentoChamadosService;
use Illuminate\Console\Command;
use Throwable;

class MonitorarChamados extends Command
{
    protected $signature = 'atendimento:monitorar {--force : Executa a verificação ignorando intervalo e horário configurados}';

    protected $description = 'Verifica chamados em aberto e registra alertas de acompanhamento';

    public function handle(MonitoramentoChamadosService $monitoramento): int
    {
        try {
            $execucao = $monitoramento->executar($this->option('force'));
        } catch (Throwable $erro) {
            $this->error('Não foi possível concluir o monitoramento: '.$erro->getMessage());

            return self::FAILURE;
        }

        if (! $execucao) {
            $this->info('Monitoramento ignorado: fora do horário ou intervalo configurado.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Monitoramento concluído: %d chamados analisados e %d ocorrências identificadas.',
            $execucao->chamados_analisados,
            $execucao->ocorrencias_identificadas,
        ));

        return self::SUCCESS;
    }
}
