@extends('layouts.app')
@section('titulo', 'Mais detalhamentos · '.$chamado->nomeSolicitante())
@section('conteudo')
@php
    $tonsComunicacao = [
        'calmo' => 'Calmo e colaborativo',
        'objetivo' => 'Objetivo',
        'preocupado' => 'Preocupado',
        'frustrado' => 'Frustrado',
        'tenso' => 'Tenso',
    ];
@endphp
<div class="mx-auto max-w-6xl space-y-6">
    <a href="{{ route('chamados.show', $chamado) }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-orange-700 dark:hover:text-orange-300"><i data-lucide="arrow-left" class="h-4 w-4"></i> Voltar ao chamado {{ $chamado->numero }}</a>

    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-slate-900 to-orange-950 px-6 py-7 text-white shadow-xl sm:px-8">
        <div class="pointer-events-none absolute -right-20 -top-32 h-80 w-80 rounded-full bg-orange-500/20 blur-3xl"></div>
        <div class="relative flex flex-wrap items-end justify-between gap-5">
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-[.18em] text-orange-300">Área interna · acesso da equipe</p>
                <h2 class="text-2xl font-bold sm:text-3xl">Mais detalhamentos</h2>
                <p class="mt-2 text-sm text-slate-300">Visão consolidada do cliente e do chamado atual para apoiar o atendimento.</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                <p class="text-xs text-slate-300">Chamado em atendimento</p>
                <p class="mt-1 font-mono text-lg font-bold">{{ $chamado->numero }}</p>
            </div>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="cartao border-orange-100 bg-gradient-to-br from-white to-orange-50 dark:border-orange-900/50 dark:from-slate-900 dark:to-orange-950/20">
            <p class="text-xs font-medium text-slate-500">Chamados neste mês</p>
            <p class="mt-2 text-3xl font-bold text-orange-700 dark:text-orange-300">{{ $chamadosEsteMes }}</p>
            <p class="mt-1 text-xs text-slate-500">Identificados por cadastro, e-mail ou CNPJ</p>
        </div>
        <div class="cartao">
            <p class="text-xs font-medium text-slate-500">Chamados no histórico</p>
            <p class="mt-2 text-3xl font-bold">{{ $totalChamados }}</p>
            <p class="mt-1 text-xs text-slate-500">Inclui o chamado atual</p>
        </div>
        <div class="cartao sm:col-span-2">
            <p class="text-xs font-medium text-slate-500">Cliente / empresa</p>
            <p class="mt-2 text-lg font-semibold">{{ $chamado->nomeSolicitante() }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $chamado->emailSolicitante() ?: 'Sem e-mail cadastrado' }}</p>
            @if ($chamado->empresa_nome || $chamado->empresa_cnpj)
                <p class="mt-1 text-xs text-slate-500">{{ $chamado->empresa_nome }}@if($chamado->empresa_cnpj) · CNPJ {{ $chamado->cnpjFormatado() }}@endif</p>
            @endif
        </div>
    </section>

    <section class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,.7fr)]">
        <div class="space-y-6">
            <article class="cartao space-y-4">
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300"><i data-lucide="sparkles" class="h-5 w-5"></i></span>
                    <div>
                        <h3 class="font-semibold">Resumo automático da solicitação</h3>
                        <p class="mt-1 text-xs text-slate-500">Consolidado a partir da descrição inicial e das mensagens do cliente. O sistema não interpreta além do que foi informado.</p>
                    </div>
                </div>
                <p class="whitespace-pre-line rounded-xl border border-sky-100 bg-sky-50/70 p-4 text-sm leading-6 dark:border-sky-900/50 dark:bg-sky-950/20">{{ $resumoSolicitacao }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60">
                        <p class="text-xs text-slate-500">Assunto</p>
                        <p class="mt-1 text-sm font-medium">{{ $chamado->assunto }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60">
                        <p class="text-xs text-slate-500">Departamento</p>
                        <p class="mt-1 text-sm font-medium">{{ $chamado->departamento->nome }}</p>
                    </div>
                </div>
            </article>

            <article class="cartao">
                <div class="mb-4 flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300"><i data-lucide="messages-square" class="h-5 w-5"></i></span>
                    <div>
                        <h3 class="font-semibold">Anotações da equipe neste atendimento</h3>
                        <p class="mt-1 text-xs text-slate-500">Registre recorrências percebidas e a comunicação observada para orientar o atendimento atual.</p>
                    </div>
                </div>
                @can('editar', $chamado)
                    <form method="POST" action="{{ route('chamados.detalhamentos.atualizar', $chamado) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="demandas-recorrentes" class="rotulo">Atendimentos ou dificuldades recorrentes</label>
                            <textarea id="demandas-recorrentes" name="demandas_recorrentes" rows="4" maxlength="3000" class="campo" placeholder="Registre fatos percebidos em atendimentos anteriores ou neste contato.">{{ old('demandas_recorrentes', $chamado->demandas_recorrentes) }}</textarea>
                        </div>
                        <div>
                            <label for="tom-comunicacao" class="rotulo">Como está a comunicação neste atendimento?</label>
                            <select id="tom-comunicacao" name="tom_comunicacao_cliente" class="campo">
                                <option value="">Não informado</option>
                                <option value="calmo" @selected(old('tom_comunicacao_cliente', $chamado->tom_comunicacao_cliente) === 'calmo')>Calmo e colaborativo</option>
                                <option value="objetivo" @selected(old('tom_comunicacao_cliente', $chamado->tom_comunicacao_cliente) === 'objetivo')>Objetivo</option>
                                <option value="preocupado" @selected(old('tom_comunicacao_cliente', $chamado->tom_comunicacao_cliente) === 'preocupado')>Preocupado</option>
                                <option value="frustrado" @selected(old('tom_comunicacao_cliente', $chamado->tom_comunicacao_cliente) === 'frustrado')>Frustrado</option>
                                <option value="tenso" @selected(old('tom_comunicacao_cliente', $chamado->tom_comunicacao_cliente) === 'tenso')>Tenso</option>
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Descreve somente a comunicação observada neste chamado, não um perfil permanente do cliente.</p>
                        </div>
                        <button class="btn-primario w-full justify-center"><i data-lucide="save" class="h-4 w-4"></i> Salvar anotações deste atendimento</button>
                    </form>
                @else
                    <div class="space-y-4">
                        <div><p class="rotulo">Atendimentos ou dificuldades recorrentes</p><p class="whitespace-pre-line text-sm">{{ $chamado->demandas_recorrentes ?: 'Ainda não registrado.' }}</p></div>
                        <div><p class="rotulo">Comunicação observada</p><p class="text-sm">{{ $tonsComunicacao[$chamado->tom_comunicacao_cliente] ?? 'Ainda não informada.' }}</p></div>
                    </div>
                @endcan
            </article>
        </div>

        <aside class="cartao">
            <div class="mb-4 flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300"><i data-lucide="history" class="h-5 w-5"></i></span>
                <div>
                    <h3 class="font-semibold">Histórico de atendimentos</h3>
                    <p class="text-xs text-slate-500">Chamados relacionados ao mesmo cliente</p>
                </div>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($historicoCliente as $anterior)
                    <div class="py-3 first:pt-0">
                        <a href="{{ route('chamados.show', $anterior) }}" class="group flex items-start justify-between gap-3">
                            <span class="min-w-0">
                                <span class="font-mono text-xs font-semibold text-orange-700 group-hover:underline dark:text-orange-300">{{ $anterior->numero }}</span>
                                <span class="mt-1 block truncate text-sm font-medium">{{ $anterior->assunto }}</span>
                                <span class="mt-1 block text-xs text-slate-500">{{ $anterior->departamento->nome }} · {{ $anterior->created_at->format('d/m/Y H:i') }}</span>
                            </span>
                            <span class="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold dark:bg-slate-800">{{ $anterior->status->nome }}</span>
                        </a>
                        @foreach ($anterior->finalizacoes->whereNotNull('relatorio') as $relatorioAnterior)
                            <a href="{{ route('chamados.relatorios.show', $relatorioAnterior) }}" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-orange-700 hover:underline dark:text-orange-300"><i data-lucide="file-text" class="h-3.5 w-3.5"></i> Ver relatório de finalização</a>
                        @endforeach
                    </div>
                @empty
                    <p class="py-4 text-sm text-slate-500">Não foram encontrados atendimentos anteriores para esta identificação.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $historicoCliente->links() }}</div>
        </aside>
    </section>
</div>
@endsection
