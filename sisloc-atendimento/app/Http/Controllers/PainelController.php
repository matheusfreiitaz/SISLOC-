<?php

namespace App\Http\Controllers;

use App\Models\Chamado;
use Illuminate\Support\Collection;

class PainelController extends Controller
{
    public function index()
    {
        $usuario = auth()->user();
        $base = fn () => Chamado::visiveisPara($usuario);

        $indicadores = [
            'total' => $base()->count(),
            'abertos' => $base()->whereHas('status', fn ($q) => $q->where('slug', 'aberto'))->count(),
            'em_atendimento' => $base()->whereHas('status', fn ($q) => $q->where('slug', 'em_atendimento'))->count(),
            'aguardando_usuario' => $base()->whereHas('status', fn ($q) => $q->where('slug', 'aguardando_usuario'))->count(),
            'atrasados' => $base()->atrasados()->count(),
            'sem_responsavel' => $base()->whereNull('agente_id')->whereHas('status', fn ($q) => $q->where('final', false))->count(),
            'resolvidos' => $base()->whereHas('status', fn ($q) => $q->where('slug', 'resolvido'))->count(),
        ];

        $inicioHoje = today();
        $fimHoje = $inicioHoje->copy()->endOfDay();
        $inicioPeriodo = today()->subDays(29);
        $fimPeriodo = now();
        $indicadores['criados_hoje'] = $base()->whereBetween('created_at', [$inicioHoje, $fimHoje])->count();
        $indicadores['resolvidos_hoje'] = $base()->whereBetween('finalizado_em', [$inicioHoje, $fimHoje])->count();
        $indicadores['criados_30_dias'] = $base()->whereBetween('created_at', [$inicioPeriodo, $fimPeriodo])->count();

        $respostasRecentes = $base()->whereBetween('primeira_resposta_em', [$inicioPeriodo, $fimPeriodo])
            ->whereNotNull('created_at')->get(['created_at', 'primeira_resposta_em']);
        $duracoesPrimeiraResposta = $respostasRecentes->map(
            fn (Chamado $chamado) => max(0, $chamado->primeira_resposta_em->getTimestamp() - $chamado->created_at->getTimestamp()) / 60,
        );
        $indicadores['media_primeira_resposta_minutos'] = $duracoesPrimeiraResposta->isEmpty() ? null : round($duracoesPrimeiraResposta->avg(), 1);
        $indicadores['mediana_primeira_resposta_minutos'] = $this->mediana($duracoesPrimeiraResposta);

        $concluidosRecentes = $base()->whereBetween('finalizado_em', [$inicioPeriodo, $fimPeriodo])
            ->whereNotNull('created_at')->get(['id', 'agente_id', 'status_id', 'created_at', 'finalizado_em', 'prazo_em', 'primeira_resposta_em']);
        $duracoesResolucao = $concluidosRecentes->map(
            fn (Chamado $chamado) => max(0, $chamado->finalizado_em->getTimestamp() - $chamado->created_at->getTimestamp()) / 3600,
        );
        $indicadores['media_resolucao_horas'] = $duracoesResolucao->isEmpty() ? null : round($duracoesResolucao->avg(), 1);
        $indicadores['mediana_resolucao_horas'] = $this->mediana($duracoesResolucao);
        $indicadores['resolvidos_30_dias'] = $concluidosRecentes->count();
        $concluidosComPrazo = $concluidosRecentes->filter(fn (Chamado $chamado) => $chamado->prazo_em !== null);
        $concluidosNoPrazo = $concluidosComPrazo->filter(fn (Chamado $chamado) => $chamado->finalizado_em <= $chamado->prazo_em)->count();
        $indicadores['concluidos_com_prazo'] = $concluidosComPrazo->count();
        $indicadores['concluidos_no_prazo'] = $concluidosNoPrazo;
        $indicadores['percentual_sla'] = $concluidosComPrazo->isEmpty()
            ? null
            : round($concluidosNoPrazo / $concluidosComPrazo->count() * 100, 1);

        $emAberto = $base()->whereHas('status', fn ($query) => $query->where('final', false))
            ->with('agente:id,nome')
            ->get(['id', 'agente_id', 'status_id', 'created_at', 'prazo_em']);
        $agora = now();
        $indicadores['em_aberto_agora'] = $emAberto->count();
        $indicadores['abertos_atrasados'] = $emAberto->filter(fn (Chamado $chamado) => $chamado->prazo_em?->isPast())->count();
        $indicadores['abertos_sem_prazo'] = $emAberto->filter(fn (Chamado $chamado) => $chamado->prazo_em === null)->count();
        $faixasBacklog = [
            'Até 24 horas' => fn (Chamado $chamado) => $chamado->created_at >= $agora->copy()->subDay(),
            'De 1 a 3 dias' => fn (Chamado $chamado) => $chamado->created_at < $agora->copy()->subDay() && $chamado->created_at >= $agora->copy()->subDays(3),
            'De 3 a 7 dias' => fn (Chamado $chamado) => $chamado->created_at < $agora->copy()->subDays(3) && $chamado->created_at >= $agora->copy()->subDays(7),
            'Mais de 7 dias' => fn (Chamado $chamado) => $chamado->created_at < $agora->copy()->subDays(7),
        ];
        $idadeBacklog = collect($faixasBacklog)->map(fn ($filtro) => $emAberto->filter($filtro)->count());

        $distribuicaoStatus = $base()
            ->join('status_chamado', 'status_chamado.id', '=', 'chamados.status_id')
            ->select('status_chamado.nome')
            ->selectRaw('COUNT(chamados.id) as total')
            ->groupBy('status_chamado.id', 'status_chamado.nome', 'status_chamado.ordem')
            ->orderBy('status_chamado.ordem')
            ->get();
        $distribuicaoPrioridade = $base()
            ->join('prioridades', 'prioridades.id', '=', 'chamados.prioridade_id')
            ->select('prioridades.nome')
            ->selectRaw('COUNT(chamados.id) as total')
            ->groupBy('prioridades.id', 'prioridades.nome', 'prioridades.nivel')
            ->orderByDesc('prioridades.nivel')
            ->get();
        $distribuicaoDepartamento = $base()
            ->join('departamentos', 'departamentos.id', '=', 'chamados.departamento_id')
            ->join('status_chamado', 'status_chamado.id', '=', 'chamados.status_id')
            ->select('departamentos.nome')
            ->selectRaw('COUNT(chamados.id) as total')
            ->selectRaw('SUM(CASE WHEN status_chamado.final = 0 THEN 1 ELSE 0 END) as em_aberto')
            ->groupBy('departamentos.id', 'departamentos.nome')
            ->orderByDesc('total')
            ->get();

        $visaoEquipe = $usuario->temPermissao('chamados.ver_todos');
        $desempenhoEquipe = collect();
        if ($visaoEquipe) {
            $chamadosDoPeriodo = $base()->where(function ($query) use ($inicioPeriodo, $fimPeriodo): void {
                $query->whereBetween('created_at', [$inicioPeriodo, $fimPeriodo])
                    ->orWhereBetween('finalizado_em', [$inicioPeriodo, $fimPeriodo]);
            })->with('agente:id,nome')->get(['id', 'agente_id', 'created_at', 'primeira_resposta_em', 'finalizado_em', 'prazo_em']);
            $idsAgentes = $chamadosDoPeriodo->pluck('agente_id')->merge($emAberto->pluck('agente_id'))
                ->map(fn ($id) => (int) ($id ?? 0))->unique()->values();
            $desempenhoEquipe = $idsAgentes->map(function (int $agenteId) use ($chamadosDoPeriodo, $emAberto, $inicioPeriodo, $fimPeriodo): array {
                $doAgente = $chamadosDoPeriodo->filter(fn (Chamado $chamado) => (int) ($chamado->agente_id ?? 0) === $agenteId);
                $abertosDoAgente = $emAberto->filter(fn (Chamado $chamado) => (int) ($chamado->agente_id ?? 0) === $agenteId);
                $recebidos = $doAgente->filter(fn (Chamado $chamado) => $chamado->created_at >= $inicioPeriodo && $chamado->created_at <= $fimPeriodo);
                $concluidos = $doAgente->filter(fn (Chamado $chamado) => $chamado->finalizado_em && $chamado->finalizado_em >= $inicioPeriodo && $chamado->finalizado_em <= $fimPeriodo);
                $respostas = $recebidos->filter(fn (Chamado $chamado) => $chamado->primeira_resposta_em !== null)->map(
                    fn (Chamado $chamado) => max(0, $chamado->primeira_resposta_em->getTimestamp() - $chamado->created_at->getTimestamp()) / 60,
                );
                $comPrazo = $concluidos->filter(fn (Chamado $chamado) => $chamado->prazo_em !== null);
                $noPrazo = $comPrazo->filter(fn (Chamado $chamado) => $chamado->finalizado_em <= $chamado->prazo_em)->count();

                return [
                    'nome' => $agenteId === 0 ? 'Sem responsável' : ($doAgente->first()?->agente?->nome ?? $abertosDoAgente->first()?->agente?->nome ?? 'Atendente'),
                    'recebidos' => $recebidos->count(),
                    'concluidos' => $concluidos->count(),
                    'em_aberto' => $abertosDoAgente->count(),
                    'atrasados' => $abertosDoAgente->filter(fn (Chamado $chamado) => $chamado->prazo_em?->isPast())->count(),
                    'media_primeira_resposta' => $respostas->isEmpty() ? null : round($respostas->avg(), 1),
                    'percentual_sla' => $comPrazo->isEmpty() ? null : round($noPrazo / $comPrazo->count() * 100, 1),
                ];
            })->sortByDesc('concluidos')->values();
        }

        $inicioTendencia = today()->subDays(29);
        $fimTendencia = now();
        $criadosPorDia = $base()->whereBetween('created_at', [$inicioTendencia, $fimTendencia])
            ->get(['created_at'])->countBy(fn (Chamado $chamado) => $chamado->created_at->toDateString());
        $resolvidosPorDia = $base()->whereBetween('finalizado_em', [$inicioTendencia, $fimTendencia])
            ->get(['finalizado_em'])->countBy(fn (Chamado $chamado) => $chamado->finalizado_em->toDateString());
        $tendencia = collect(range(0, 29))->map(function (int $diasAtras) use ($inicioTendencia, $criadosPorDia, $resolvidosPorDia) {
            $dia = $inicioTendencia->copy()->addDays($diasAtras);

            return [
                'data' => $dia,
                'criados' => $criadosPorDia[$dia->toDateString()] ?? 0,
                'resolvidos' => $resolvidosPorDia[$dia->toDateString()] ?? 0,
            ];
        });

        $recentes = $base()->with(['status', 'prioridade', 'solicitante'])->latest()->limit(8)->get();
        $maiorVolumeDia = max(1, (int) $tendencia->max(fn (array $dia) => max($dia['criados'], $dia['resolvidos'])));

        return view('painel.index', compact(
            'indicadores', 'recentes', 'tendencia', 'maiorVolumeDia', 'distribuicaoStatus',
            'distribuicaoPrioridade', 'distribuicaoDepartamento', 'idadeBacklog', 'visaoEquipe', 'desempenhoEquipe',
        ));
    }

    private function mediana(Collection $valores): ?float
    {
        $ordenados = $valores->sort()->values();
        $quantidade = $ordenados->count();
        if ($quantidade === 0) {
            return null;
        }

        $meio = intdiv($quantidade, 2);
        $mediana = $quantidade % 2
            ? $ordenados[$meio]
            : ($ordenados[$meio - 1] + $ordenados[$meio]) / 2;

        return round($mediana, 1);
    }
}
