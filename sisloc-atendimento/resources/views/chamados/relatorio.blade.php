@extends('layouts.app')
@section('titulo', 'Relatório do chamado '.$relatorio['chamado']['numero'])
@section('conteudo')
@php
    $dadosChamado = $relatorio['chamado'];
    $cliente = $relatorio['cliente'];
    $resolucao = $relatorio['resolucao'];
    $minutos = (int) ($dadosChamado['tempo_total_minutos'] ?? 0);
    $formatarData = fn ($valor) => $valor ? \Illuminate\Support\Carbon::parse($valor)->format('d/m/Y H:i') : 'Não registrado';
    $formatarTamanho = fn ($bytes) => $bytes >= 1048576 ? number_format($bytes / 1048576, 1).' MB' : number_format($bytes / 1024, 0).' KB';
    $tonsComunicacao = ['calmo' => 'Calmo e colaborativo', 'objetivo' => 'Objetivo', 'preocupado' => 'Preocupado', 'frustrado' => 'Frustrado', 'tenso' => 'Tenso'];
@endphp
<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('chamados.show', $finalizacao->chamado) }}" class="no-print inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-orange-700 dark:hover:text-orange-300"><i data-lucide="arrow-left" class="h-4 w-4"></i> Voltar ao chamado {{ $dadosChamado['numero'] }}</a>
        <button type="button" onclick="window.print()" class="btn-neutro no-print"><i data-lucide="printer" class="h-4 w-4"></i> Imprimir relatório</button>
    </div>

    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-slate-900 to-orange-950 px-6 py-8 text-white shadow-xl sm:px-9">
        <div class="pointer-events-none absolute -right-16 -top-28 h-80 w-80 rounded-full bg-orange-500/20 blur-3xl"></div>
        <div class="relative">
            <p class="inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1 text-xs font-semibold text-emerald-200"><i data-lucide="file-check-2" class="h-4 w-4"></i> Relatório gerado automaticamente ao finalizar</p>
            <p class="mt-5 font-mono text-sm text-orange-200">{{ $dadosChamado['numero'] }}</p>
            <h2 class="mt-1 text-2xl font-bold sm:text-3xl">{{ $dadosChamado['assunto'] }}</h2>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300">Consolidação do pedido, contexto do cliente, conversa, alterações e resolução registrada. O relatório preserva as informações de origem e não cria inferências.</p>
            <div class="mt-5 flex flex-wrap gap-2">
                <span class="rounded-full bg-white/10 px-3 py-1 text-xs">{{ $dadosChamado['departamento'] }}</span>
                <span class="rounded-full bg-white/10 px-3 py-1 text-xs">{{ $dadosChamado['prioridade'] }}</span>
                <span class="rounded-full bg-emerald-400/15 px-3 py-1 text-xs text-emerald-200">{{ $dadosChamado['status_final'] }}</span>
            </div>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="cartao"><p class="text-xs text-slate-500">Abertura</p><p class="mt-2 font-semibold">{{ $formatarData($dadosChamado['aberto_em']) }}</p></div>
        <div class="cartao"><p class="text-xs text-slate-500">Finalização</p><p class="mt-2 font-semibold">{{ $formatarData($dadosChamado['finalizado_em']) }}</p></div>
        <div class="cartao"><p class="text-xs text-slate-500">Duração do atendimento</p><p class="mt-2 font-semibold">{{ intdiv($minutos, 60) }}h {{ $minutos % 60 }}min</p></div>
        <div class="cartao"><p class="text-xs text-slate-500">Chamados do cliente neste mês</p><p class="mt-2 font-semibold">{{ $cliente['chamados_abertos_no_mes'] }}</p></div>
    </section>

    <section class="grid gap-6 lg:grid-cols-2">
        <article class="cartao">
            <h3 class="mb-4 flex items-center gap-2 font-semibold"><i data-lucide="user-round" class="h-4 w-4 text-orange-600"></i> Identificação do cliente</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Nome</dt><dd class="text-right font-medium">{{ $cliente['nome'] }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">E-mail</dt><dd class="text-right">{{ $cliente['email'] ?: 'Não informado' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Empresa</dt><dd class="text-right">{{ $cliente['empresa'] ?: 'Não informada' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">CNPJ</dt><dd class="text-right">{{ $cliente['cnpj'] ?: 'Não informado' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Total identificado</dt><dd class="text-right">{{ $cliente['total_chamados_identificados'] }} chamado(s)</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Categoria / subcategoria</dt><dd class="text-right">{{ $dadosChamado['categoria'] ?: 'Não informada' }} / {{ $dadosChamado['subcategoria'] ?: 'Não informada' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Primeira resposta</dt><dd class="text-right">{{ $formatarData($dadosChamado['primeira_resposta_em']) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Prazo</dt><dd class="text-right">{{ $formatarData($dadosChamado['prazo_em']) }}</dd></div>
            </dl>
        </article>
        <article class="cartao">
            <h3 class="mb-4 flex items-center gap-2 font-semibold"><i data-lucide="clipboard-check" class="h-4 w-4 text-orange-600"></i> Resolução registrada</h3>
            <div class="space-y-4 text-sm">
                <div><p class="rotulo">Motivo</p><p class="font-medium">{{ $resolucao['motivo'] }}</p></div>
                <div><p class="rotulo">Solução aplicada</p><p class="whitespace-pre-line leading-6">{{ $resolucao['descricao'] }}</p></div>
                <div><p class="rotulo">Observações de encerramento</p><p class="whitespace-pre-line leading-6">{{ $resolucao['observacoes'] ?: 'Nenhuma observação registrada.' }}</p></div>
                <div><p class="rotulo">Finalizado por</p><p>{{ $resolucao['finalizado_por'] }}</p></div>
            </div>
        </article>
    </section>

    <article class="cartao">
        <h3 class="mb-3 flex items-center gap-2 font-semibold"><i data-lucide="text-search" class="h-4 w-4 text-sky-600"></i> Resumo automático do que foi solicitado</h3>
        <p class="whitespace-pre-line rounded-xl border border-sky-100 bg-sky-50/70 p-4 text-sm leading-6 dark:border-sky-900/50 dark:bg-sky-950/20">{{ $dadosChamado['resumo_automatico'] }}</p>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div><p class="rotulo">Descrição original</p><p class="whitespace-pre-line text-sm leading-6">{{ $dadosChamado['descricao_original'] }}</p></div>
            <div><p class="rotulo">Funcionamento esperado</p><p class="whitespace-pre-line text-sm leading-6">{{ $finalizacao->chamado->funcionamento_esperado ?: 'Não informado.' }}</p></div>
        </div>
    </article>

    <section class="grid gap-6 lg:grid-cols-2">
        <article class="cartao">
            <h3 class="mb-4 flex items-center gap-2 font-semibold"><i data-lucide="message-circle-heart" class="h-4 w-4 text-violet-600"></i> Contexto de atendimento</h3>
            <div class="space-y-4 text-sm">
                <div><p class="rotulo">Recorrências registradas pela equipe</p><p class="whitespace-pre-line">{{ $cliente['demandas_recorrentes_informadas_pela_equipe'] ?: 'Não registradas.' }}</p></div>
                <div><p class="rotulo">Comunicação observada neste atendimento</p><p>{{ $tonsComunicacao[$cliente['comunicacao_observada_neste_atendimento']] ?? 'Não informada.' }}</p></div>
                <p class="rounded-lg bg-slate-50 p-3 text-xs leading-5 text-slate-500 dark:bg-slate-800/60">A comunicação é uma observação restrita a este chamado e não constitui avaliação permanente do cliente.</p>
            </div>
        </article>
        <article class="cartao">
            <h3 class="mb-4 flex items-center gap-2 font-semibold"><i data-lucide="folder-clock" class="h-4 w-4 text-orange-600"></i> Atendimentos anteriores</h3>
            @forelse ($cliente['historico_de_chamados'] as $caso)
                <div class="border-b border-slate-100 py-3 first:pt-0 last:border-0 dark:border-slate-800">
                    <div class="flex justify-between gap-3"><span class="font-mono text-xs font-semibold text-orange-700 dark:text-orange-300">{{ $caso['numero'] }}</span><span class="text-xs text-slate-500">{{ $caso['status'] }}</span></div>
                    <p class="mt-1 text-sm font-medium">{{ $caso['assunto'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $caso['departamento'] }} · {{ $formatarData($caso['aberto_em']) }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">Nenhum chamado anterior localizado para o cliente.</p>
            @endforelse
        </article>
    </section>

    <article class="cartao">
        <h3 class="mb-4 flex items-center gap-2 font-semibold"><i data-lucide="messages-square" class="h-4 w-4 text-orange-600"></i> Transcrição integral do atendimento</h3>
        @forelse ($relatorio['conversa'] as $mensagem)
            <div class="border-b border-slate-100 py-4 last:border-0 dark:border-slate-800">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-semibold">{{ $mensagem['remetente'] }} <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500 dark:bg-slate-800">{{ $mensagem['tipo'] }}</span></p>
                    <time class="text-xs text-slate-500">{{ $formatarData($mensagem['data']) }}</time>
                </div>
                <div class="mt-2 whitespace-pre-line text-sm leading-6">{!! app(\App\Services\MensagemChamadoSanitizer::class)->sanitizar($mensagem['mensagem']) !!}</div>
                @if ($mensagem['anexos'])
                    <ul class="mt-2 space-y-1">
                        @foreach ($mensagem['anexos'] as $anexo)
                            <li class="flex items-center gap-2 text-xs text-slate-500"><i data-lucide="paperclip" class="h-3.5 w-3.5"></i>{{ $anexo['nome'] }} · {{ $formatarTamanho($anexo['tamanho_bytes']) }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @empty
            <p class="text-sm text-slate-500">Não há mensagens registradas.</p>
        @endforelse
    </article>

    <section class="grid gap-6 lg:grid-cols-2">
        <article class="cartao">
            <h3 class="mb-4 flex items-center gap-2 font-semibold"><i data-lucide="paperclip" class="h-4 w-4 text-orange-600"></i> Documentos da abertura</h3>
            @forelse ($relatorio['anexos_iniciais'] as $anexo)
                <p class="flex items-center gap-2 border-b border-slate-100 py-2 text-sm last:border-0 dark:border-slate-800"><i data-lucide="file" class="h-4 w-4 text-slate-400"></i>{{ $anexo['nome'] }}<span class="ml-auto text-xs text-slate-500">{{ $formatarTamanho($anexo['tamanho_bytes']) }}</span></p>
            @empty
                <p class="text-sm text-slate-500">Nenhum documento anexado na abertura.</p>
            @endforelse
        </article>
        <article class="cartao">
            <h3 class="mb-4 flex items-center gap-2 font-semibold"><i data-lucide="list-restart" class="h-4 w-4 text-orange-600"></i> Auditoria de alterações</h3>
            @forelse ($relatorio['historico_de_alteracoes'] as $evento)
                <div class="border-b border-slate-100 py-2 last:border-0 dark:border-slate-800">
                    <p class="text-xs font-semibold">{{ ucfirst($evento['campo']) }}</p>
                    <p class="mt-1 break-words text-xs text-slate-600 dark:text-slate-300">{{ $evento['de'] ?: '—' }} → {{ $evento['para'] ?: '—' }}</p>
                    <p class="mt-1 text-[10px] text-slate-500">{{ $formatarData($evento['data']) }} · {{ $evento['colaborador'] ?: 'Sistema' }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">Nenhuma alteração adicional registrada.</p>
            @endforelse
        </article>
    </section>

    <footer class="cartao flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
        <span>{{ $relatorio['geracao']['metodo'] }} · {{ $formatarData($relatorio['geracao']['gerado_em']) }}</span>
        <span>{{ $relatorio['geracao']['ia'] }}</span>
    </footer>
</div>
<style>
    @media print {
        body > div.flex.min-h-screen > aside,
        body > div.flex.min-h-screen > div.flex-1 > header,
        .no-print { display: none !important; }
        body > div.flex.min-h-screen > div.flex-1 { padding-left: 0 !important; }
        body > div.flex.min-h-screen > div.flex-1 > main { padding: 0 !important; }
        body, .cartao { background: white !important; box-shadow: none !important; color: black !important; }
    }
</style>
@endsection
