<?php

namespace App\Services;

use App\Models\Chamado;
use App\Models\FinalizacaoChamado;
use Illuminate\Database\Eloquent\Builder;

class RelatorioAtendimentoService
{
    public function resumoSolicitacao(Chamado $chamado): string
    {
        $relatos = $chamado->interacoes()
            ->where('tipo', '!=', 'nota_interna')
            ->where(function ($consulta) use ($chamado) {
                $consulta->where('remetente_tipo', 'cliente');
                if ($chamado->solicitante_id) {
                    $consulta->orWhere('usuario_id', $chamado->solicitante_id);
                }
            })
            ->orderBy('id')
            ->pluck('mensagem')
            ->map(fn (string $mensagem) => trim($mensagem))
            ->filter()
            ->unique()
            ->values();

        $resumo = 'Solicitação inicial: '.trim($chamado->descricao);
        if ($relatos->isNotEmpty()) {
            $resumo .= "\n\nComplementos informados pelo cliente durante o atendimento:\n- ".$relatos->implode("\n- ");
        }

        return $resumo;
    }

    public function gerar(Chamado $chamado, FinalizacaoChamado $finalizacao): array
    {
        $chamado->load([
            'solicitante', 'departamento', 'categoria', 'subcategoria', 'prioridade', 'status',
            'agente', 'historico.usuario', 'anexos', 'interacoes.usuario', 'interacoes.anexos',
        ]);
        $casosAnteriores = $this->chamadosRelacionados($chamado)
            ->where('chamados.id', '!=', $chamado->id)
            ->with(['status', 'departamento'])
            ->latest('chamados.created_at')
            ->get([
                'chamados.id', 'chamados.numero', 'chamados.assunto', 'chamados.descricao',
                'chamados.status_id', 'chamados.departamento_id', 'chamados.created_at', 'chamados.finalizado_em',
            ]);
        $inicioMes = now()->startOfMonth();
        $fimMes = now()->endOfMonth();
        $duracaoMinutos = $chamado->created_at->diffInMinutes($finalizacao->created_at);
        $resumo = $this->resumoSolicitacao($chamado);

        return [
            'versao' => 1,
            'geracao' => [
                'metodo' => 'Relatório estruturado gerado automaticamente pelo sistema',
                'ia' => 'Nenhum provedor de IA está configurado; os dados foram consolidados localmente, sem inferências.',
                'gerado_em' => now()->toIso8601String(),
            ],
            'chamado' => [
                'numero' => $chamado->numero,
                'assunto' => $chamado->assunto,
                'descricao_original' => $chamado->descricao,
                'resumo_automatico' => $resumo,
                'departamento' => $chamado->departamento->nome,
                'categoria' => $chamado->categoria?->nome,
                'subcategoria' => $chamado->subcategoria?->nome,
                'prioridade' => $chamado->prioridade->nome,
                'status_final' => $chamado->status->nome,
                'aberto_em' => $chamado->created_at->toIso8601String(),
                'finalizado_em' => $finalizacao->created_at->toIso8601String(),
                'tempo_total_minutos' => $duracaoMinutos,
                'primeira_resposta_em' => $chamado->primeira_resposta_em?->toIso8601String(),
                'prazo_em' => $chamado->prazo_em?->toIso8601String(),
            ],
            'cliente' => [
                'nome' => $chamado->nomeSolicitante(),
                'email' => $chamado->emailSolicitante(),
                'empresa' => $chamado->empresa_nome,
                'cnpj' => $chamado->cnpjFormatado(),
                'chamados_abertos_no_mes' => $this->chamadosRelacionados($chamado)
                    ->whereBetween('chamados.created_at', [$inicioMes, $fimMes])->count(),
                'total_chamados_identificados' => $casosAnteriores->count() + 1,
                'demandas_recorrentes_informadas_pela_equipe' => $chamado->demandas_recorrentes,
                'comunicacao_observada_neste_atendimento' => $chamado->tom_comunicacao_cliente,
                'historico_de_chamados' => $casosAnteriores->map(fn (Chamado $caso) => [
                    'numero' => $caso->numero,
                    'assunto' => $caso->assunto,
                    'descricao' => $caso->descricao,
                    'status' => $caso->status->nome,
                    'departamento' => $caso->departamento->nome,
                    'aberto_em' => $caso->created_at->toIso8601String(),
                    'finalizado_em' => $caso->finalizado_em?->toIso8601String(),
                ])->all(),
            ],
            'resolucao' => [
                'motivo' => $finalizacao->motivo->nome,
                'descricao' => $finalizacao->descricao_solucao,
                'observacoes' => $finalizacao->observacoes,
                'finalizado_por' => $finalizacao->usuario->nome,
            ],
            'conversa' => $chamado->interacoes->map(fn ($interacao) => [
                'data' => $interacao->created_at->toIso8601String(),
                'remetente' => $interacao->remetente_nome ?: $interacao->usuario?->nome ?: 'Atendimento SISLOC',
                'tipo' => $interacao->tipo === 'nota_interna' ? 'Nota interna' : 'Mensagem',
                'mensagem' => $interacao->mensagem,
                'anexos' => $interacao->anexos->map(fn ($anexo) => [
                    'nome' => $anexo->nome_original,
                    'tamanho_bytes' => $anexo->tamanho,
                ])->all(),
            ])->all(),
            'anexos_iniciais' => $chamado->anexos->whereNull('interacao_id')->map(fn ($anexo) => [
                'nome' => $anexo->nome_original,
                'tamanho_bytes' => $anexo->tamanho,
            ])->values()->all(),
            'historico_de_alteracoes' => $chamado->historico->map(fn ($evento) => [
                'data' => $evento->created_at->toIso8601String(),
                'campo' => $evento->campo,
                'de' => $evento->valor_anterior,
                'para' => $evento->valor_novo,
                'colaborador' => $evento->usuario?->nome,
            ])->all(),
        ];
    }

    private function chamadosRelacionados(Chamado $chamado): Builder
    {
        $email = $chamado->emailSolicitante();

        return Chamado::query()->where(function ($consulta) use ($chamado, $email) {
            $hasIdentity = false;
            if ($chamado->solicitante_id) {
                $consulta->where('chamados.solicitante_id', $chamado->solicitante_id);
                $hasIdentity = true;
            }
            if ($email) {
                $method = $hasIdentity ? 'orWhere' : 'where';
                $consulta->{$method}('chamados.solicitante_email', $email);
                $hasIdentity = true;
            }
            if ($chamado->empresa_cnpj) {
                $method = $hasIdentity ? 'orWhere' : 'where';
                $consulta->{$method}('chamados.empresa_cnpj', $chamado->empresa_cnpj);
                $hasIdentity = true;
            }
            if (! $hasIdentity) {
                $consulta->whereRaw('1 = 0');
            }
        });
    }
}
