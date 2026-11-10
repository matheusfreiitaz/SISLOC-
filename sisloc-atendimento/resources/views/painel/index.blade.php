@extends('layouts.app')
@section('titulo', 'Início')
@section('conteudo')
@php
    $cartoes = [
        ['Chamados visíveis', $indicadores['total'], 'ticket', route('chamados.index')],
        ['Abertos', $indicadores['abertos'], 'inbox', route('chamados.index', ['status_id' => \App\Models\StatusChamado::idPorSlug('aberto')])],
        ['Em atendimento', $indicadores['em_atendimento'], 'loader', route('chamados.index', ['status_id' => \App\Models\StatusChamado::idPorSlug('em_atendimento')])],
        ['Aguardando usuário', $indicadores['aguardando_usuario'], 'hourglass', route('chamados.index', ['status_id' => \App\Models\StatusChamado::idPorSlug('aguardando_usuario')])],
        ['Atrasados', $indicadores['atrasados'], 'alarm-clock', route('chamados.index', ['atrasados' => 1])],
        ['Sem responsável', $indicadores['sem_responsavel'], 'user-x', route('chamados.index', ['sem_responsavel' => 1])],
        ['Resolvidos', $indicadores['resolvidos'], 'check-circle-2', route('chamados.index', ['status_id' => \App\Models\StatusChamado::idPorSlug('resolvido')])],
    ];
    $taxaSla = $indicadores['percentual_sla'] === null ? '—' : number_format($indicadores['percentual_sla'], 1, ',', '.').'%';
    $primeiraRespostaMedia = $indicadores['media_primeira_resposta_minutos'] === null ? '—' : number_format($indicadores['media_primeira_resposta_minutos'], 1, ',', '.').' min';
    $primeiraRespostaMediana = $indicadores['mediana_primeira_resposta_minutos'] === null ? '—' : number_format($indicadores['mediana_primeira_resposta_minutos'], 1, ',', '.').' min';
    $resolucaoMedia = $indicadores['media_resolucao_horas'] === null ? '—' : number_format($indicadores['media_resolucao_horas'], 1, ',', '.').' h';
    $resolucaoMediana = $indicadores['mediana_resolucao_horas'] === null ? '—' : number_format($indicadores['mediana_resolucao_horas'], 1, ',', '.').' h';
    $metricasDiarias = [
        ['Atendimentos recebidos hoje', $indicadores['criados_hoje'], 'inbox', 'Abertos desde 00:00'],
        ['Atendimentos concluídos hoje', $indicadores['resolvidos_hoje'], 'circle-check', 'Finalizados desde 00:00'],
        ['Recebidos · 30 dias', $indicadores['criados_30_dias'], 'calendar-days', 'Janela móvel até agora'],
        ['Concluídos · 30 dias', $indicadores['resolvidos_30_dias'], 'badge-check', 'Finalizados no período'],
        ['Em aberto agora', $indicadores['em_aberto_agora'], 'inbox', 'Todos os status não finais'],
        ['Atrasados agora', $indicadores['abertos_atrasados'], 'alarm-clock', 'Prazo vencido e ainda em aberto'],
        ['SLA cumprido · 30 dias', $taxaSla, 'shield-check', $indicadores['concluidos_no_prazo'].' de '.$indicadores['concluidos_com_prazo'].' concluídos com prazo'],
        ['1ª resposta média · 30 dias', $primeiraRespostaMedia, 'timer', 'Média dos chamados respondidos'],
        ['1ª resposta mediana · 30 dias', $primeiraRespostaMediana, 'gauge', 'Metade recebeu resposta até este tempo'],
        ['Resolução média · 30 dias', $resolucaoMedia, 'clock-3', 'Tempo entre abertura e conclusão'],
        ['Resolução mediana · 30 dias', $resolucaoMediana, 'hourglass', 'Metade foi concluída até este tempo'],
    ];
@endphp
<section aria-labelledby="visao-geral-heading">
    <div class="mb-3">
        <h2 id="visao-geral-heading" class="font-semibold">Visão geral dos atendimentos</h2>
        <p class="mt-1 text-xs text-slate-500">Chamados disponíveis para o seu perfil e departamento.</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($cartoes as [$titulo, $valor, $icone, $link])
        <a href="{{ $link }}" class="cartao transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-sm text-slate-500">{{ $titulo }}</span>
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-orange-100 text-orange-600 dark:bg-orange-500/15"><i data-lucide="{{ $icone }}" class="h-4 w-4"></i></span>
            </div>
            <p class="mt-3 text-3xl font-bold">{{ $valor }}</p>
        </a>
    @endforeach
</div>
</section>

<section class="mt-7" aria-labelledby="metricas-heading">
    <div class="mb-3">
        <h2 id="metricas-heading" class="font-semibold">Indicadores de produtividade</h2>
        <p class="mt-1 text-xs text-slate-500">Volume, backlog, cumprimento de prazo e tempos médios e medianos na janela móvel de 30 dias.</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($metricasDiarias as [$titulo, $valor, $icone, $detalhe])
            <div class="cartao">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm text-slate-500">{{ $titulo }}</span>
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-orange-100 text-orange-600 dark:bg-orange-500/15"><i data-lucide="{{ $icone }}" class="h-4 w-4"></i></span>
                </div>
                <p class="mt-3 text-3xl font-bold">{{ $valor }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $detalhe }}</p>
            </div>
        @endforeach
    </div>
</section>

<section class="cartao mt-7" aria-labelledby="tendencia-heading">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 id="tendencia-heading" class="font-semibold">Atendimentos nos últimos 30 dias</h2>
            <p class="mt-1 text-xs text-slate-500">Chamados recebidos e concluídos por dia; passe sobre as barras para ver os totais.</p>
        </div>
        <div class="flex flex-wrap gap-4 text-xs text-slate-500">
            <span class="inline-flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-sm bg-orange-500"></i>Recebidos</span>
            <span class="inline-flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></i>Concluídos</span>
        </div>
    </div>
    <div class="mt-6 overflow-x-auto pb-2">
    <div class="grid min-w-[960px] gap-1" style="grid-template-columns: repeat(30, minmax(24px, 1fr));" role="img" aria-label="Gráfico de chamados recebidos e concluídos nos últimos trinta dias">
        @foreach ($tendencia as $dia)
            @php
                $alturaCriados = $dia['criados'] ? max(8, (int) round($dia['criados'] / $maiorVolumeDia * 100)) : 3;
                $alturaResolvidos = $dia['resolvidos'] ? max(8, (int) round($dia['resolvidos'] / $maiorVolumeDia * 100)) : 3;
            @endphp
            <div class="min-w-0 text-center">
                <div class="mb-2 flex h-32 items-end justify-center gap-0.5 border-b border-slate-100 px-0.5 dark:border-slate-800">
                    <span class="w-2/5 rounded-t bg-orange-500 sm:max-w-7" style="height: {{ $alturaCriados }}%" title="{{ $dia['criados'] }} recebidos" aria-label="{{ $dia['criados'] }} recebidos"></span>
                    <span class="w-2/5 rounded-t bg-emerald-500 sm:max-w-7" style="height: {{ $alturaResolvidos }}%" title="{{ $dia['resolvidos'] }} concluídos" aria-label="{{ $dia['resolvidos'] }} concluídos"></span>
                </div>
                <span class="block text-[9px] font-medium text-slate-500">{{ $dia['data']->translatedFormat('D') }}</span>
                <span class="mt-1 block truncate text-[9px] text-slate-400">{{ $dia['data']->format('d/m') }}</span>
            </div>
        @endforeach
    </div>
    </div>
</section>

<section class="mt-7 grid gap-6 xl:grid-cols-2" aria-label="Detalhamento da fila">
    <article class="cartao">
        <div class="mb-4">
            <h2 class="font-semibold">Idade da fila em aberto</h2>
            <p class="mt-1 text-xs text-slate-500">Tempo desde a abertura de cada chamado não finalizado.</p>
        </div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @foreach ($idadeBacklog as $faixa => $total)
                <div class="flex items-center justify-between py-2.5 text-sm">
                    <span class="text-slate-600 dark:text-slate-300">{{ $faixa }}</span>
                    <span class="font-semibold tabular-nums">{{ $total }}</span>
                </div>
            @endforeach
            <div class="flex items-center justify-between py-2.5 text-sm">
                <span class="text-slate-600 dark:text-slate-300">Sem prazo de SLA</span>
                <span class="font-semibold tabular-nums">{{ $indicadores['abertos_sem_prazo'] }}</span>
            </div>
        </div>
    </article>

    <article class="cartao">
        <div class="mb-4">
            <h2 class="font-semibold">Distribuição por status</h2>
            <p class="mt-1 text-xs text-slate-500">Todos os chamados visíveis para seu perfil.</p>
        </div>
        <div class="space-y-3">
            @forelse ($distribuicaoStatus as $status)
                @php($participacao = $indicadores['total'] ? round($status->total / $indicadores['total'] * 100) : 0)
                <div>
                    <div class="mb-1 flex justify-between gap-3 text-sm"><span>{{ $status->nome }}</span><span class="font-semibold tabular-nums">{{ $status->total }} <span class="font-normal text-slate-400">· {{ $participacao }}%</span></span></div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div class="h-full rounded-full bg-orange-500" style="width: {{ $participacao }}%"></div></div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Sem chamados para exibir.</p>
            @endforelse
        </div>
    </article>

    <article class="cartao">
        <div class="mb-4">
            <h2 class="font-semibold">Distribuição por prioridade</h2>
            <p class="mt-1 text-xs text-slate-500">Volume atual por nível de prioridade.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs text-slate-500 dark:border-slate-700"><tr><th class="py-2 font-medium">Prioridade</th><th class="py-2 text-right font-medium">Chamados</th><th class="py-2 text-right font-medium">Participação</th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($distribuicaoPrioridade as $prioridade)
                        <tr><td class="py-2.5">{{ $prioridade->nome }}</td><td class="py-2.5 text-right font-semibold tabular-nums">{{ $prioridade->total }}</td><td class="py-2.5 text-right tabular-nums">{{ $indicadores['total'] ? number_format($prioridade->total / $indicadores['total'] * 100, 1, ',', '.') : '0,0' }}%</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-3 text-slate-500">Sem chamados para exibir.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>

    <article class="cartao">
        <div class="mb-4">
            <h2 class="font-semibold">Volume por departamento</h2>
            <p class="mt-1 text-xs text-slate-500">Total visível e fila não finalizada em cada departamento.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs text-slate-500 dark:border-slate-700"><tr><th class="py-2 font-medium">Departamento</th><th class="py-2 text-right font-medium">Total</th><th class="py-2 text-right font-medium">Em aberto</th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($distribuicaoDepartamento as $departamento)
                        <tr><td class="py-2.5">{{ $departamento->nome }}</td><td class="py-2.5 text-right font-semibold tabular-nums">{{ $departamento->total }}</td><td class="py-2.5 text-right tabular-nums">{{ $departamento->em_aberto }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-3 text-slate-500">Sem departamentos para exibir.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
</section>

@if ($visaoEquipe)
    <section class="cartao mt-7" aria-labelledby="desempenho-equipe-heading">
        <div class="mb-4">
            <h2 id="desempenho-equipe-heading" class="font-semibold">Desempenho por atendente · 30 dias</h2>
            <p class="mt-1 text-xs text-slate-500">Chamados recebidos e concluídos no período, fila atual e SLA dos concluídos com prazo.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs text-slate-500 dark:border-slate-700"><tr><th class="py-2 font-medium">Atendente</th><th class="py-2 text-right font-medium">Recebidos</th><th class="py-2 text-right font-medium">Concluídos</th><th class="py-2 text-right font-medium">Em aberto</th><th class="py-2 text-right font-medium">Atrasados</th><th class="py-2 text-right font-medium">1ª resposta</th><th class="py-2 text-right font-medium">SLA</th></tr></thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($desempenhoEquipe as $atendente)
                        <tr><td class="py-2.5 font-medium">{{ $atendente['nome'] }}</td><td class="py-2.5 text-right tabular-nums">{{ $atendente['recebidos'] }}</td><td class="py-2.5 text-right tabular-nums">{{ $atendente['concluidos'] }}</td><td class="py-2.5 text-right tabular-nums">{{ $atendente['em_aberto'] }}</td><td class="py-2.5 text-right tabular-nums {{ $atendente['atrasados'] ? 'font-semibold text-amber-700 dark:text-amber-300' : '' }}">{{ $atendente['atrasados'] }}</td><td class="py-2.5 text-right tabular-nums">{{ $atendente['media_primeira_resposta'] === null ? '—' : number_format($atendente['media_primeira_resposta'], 1, ',', '.').' min' }}</td><td class="py-2.5 text-right tabular-nums">{{ $atendente['percentual_sla'] === null ? '—' : number_format($atendente['percentual_sla'], 1, ',', '.').'%' }}</td></tr>
                    @empty
                        <tr><td colspan="7" class="py-3 text-slate-500">Sem movimentação no período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endif

<section class="cartao mt-7">
    <h2 class="mb-3 font-semibold">Chamados recentes</h2>
    @include('chamados._tabela', ['chamados' => $recentes])
</section>
@endsection
