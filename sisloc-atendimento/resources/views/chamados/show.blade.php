@extends('layouts.app')
@section('titulo', 'Chamado '.$chamado->numero)
@section('conteudo')
@php
    $finalizado = $chamado->estaFinalizado();
    $tempoResposta = $chamado->primeira_resposta_em ? $chamado->created_at->diffForHumans($chamado->primeira_resposta_em, true) : null;
    $tempoTotal = $chamado->finalizado_em ? $chamado->created_at->diffForHumans($chamado->finalizado_em, true) : $chamado->created_at->diffForHumans(null, true);
@endphp
<div class="grid gap-6 xl:grid-cols-3">
<div class="space-y-6 xl:col-span-2">
    <div class="cartao">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="font-mono text-xs text-slate-500">{{ $chamado->numero }}</p>
                <h2 class="text-xl font-bold">{{ $chamado->assunto }}</h2>
            </div>
            <div class="flex gap-2">
                <x-selo-status :cor="$chamado->prioridade->cor" :texto="$chamado->prioridade->nome" />
                <x-selo-status :cor="$chamado->status->cor" :texto="$chamado->status->nome" />
            </div>
        </div>
        <p class="mt-4 whitespace-pre-line text-sm">{{ $chamado->descricao }}</p>
        @if ($chamado->funcionamento_esperado)
            <div class="mt-4 rounded-xl bg-orange-50 p-3 text-sm dark:bg-orange-500/10">
                <b>Funcionamento esperado</b><p class="mt-1 whitespace-pre-line">{{ $chamado->funcionamento_esperado }}</p>
            </div>
        @endif
        @if ($chamado->anexos->whereNull('interacao_id')->isNotEmpty())
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($chamado->anexos->whereNull('interacao_id') as $a)
                    <a class="btn-neutro" href="{{ route('anexos.baixar', $a) }}"><i data-lucide="paperclip" class="h-4 w-4"></i> {{ $a->nome_original }}</a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($finalizado && $chamado->finalizacao)
        <div class="cartao border-emerald-300 dark:border-emerald-800">
            <p class="text-sm"><b>Motivo:</b> {{ $chamado->finalizacao->motivo->nome }}</p>
            <p class="mt-1 whitespace-pre-line text-sm"><b>Solução:</b> {{ $chamado->finalizacao->descricao_solucao }}</p>
    if (fileInput && fileList && fileError) {
            <p class="mt-2 text-xs text-slate-500">Por {{ $chamado->finalizacao->usuario->nome }} em {{ $chamado->finalizacao->created_at->format('d/m/Y H:i') }}</p>
            @can('verNotasInternas', $chamado)
                @if ($chamado->finalizacao->relatorio)
                    <a href="{{ route('chamados.relatorios.show', $chamado->finalizacao) }}" class="btn-neutro mt-4 w-full justify-center"><i data-lucide="file-chart-column-increasing" class="h-4 w-4"></i> Abrir relatório detalhado</a>
                @endif
            @endcan
        </div>
    @endif

    <div class="cartao">
        <h3 class="mb-4 font-semibold">Interações
            @if ($mensagensNaoLidas > 0)<span class="ml-2 rounded-full bg-orange-500 px-2 py-0.5 text-xs text-white">{{ $mensagensNaoLidas }} nova(s)</span>@endif
        </h3>
        <div class="relative">
        <ol id="conversa-interna" data-ultimo="{{ $interacoes->last()?->id ?? 0 }}" data-atualizado="{{ now()->subSeconds(2)->toDateTimeString() }}" data-url="{{ route('chamados.mensagens', $chamado) }}" class="max-h-[65vh] min-h-48 space-y-4 overflow-y-auto scroll-smooth overscroll-contain pr-2">
            @forelse ($interacoes as $i)
                <li data-interacao-id="{{ $i->id }}" class="flex {{ $i->remetente_tipo === 'cliente' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[92%] rounded-2xl p-3 text-sm shadow-sm sm:max-w-[82%] {{ $i->tipo === 'nota_interna' ? 'border border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100' : ($i->remetente_tipo === 'cliente' ? 'border border-slate-200 bg-white text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100' : 'border border-[#ffb45c] bg-[#ffb45c] text-[#452600]') }}">
                        <div class="mb-1 flex justify-between gap-5 text-xs {{ $i->tipo === 'nota_interna' ? 'text-amber-700 dark:text-amber-300' : ($i->remetente_tipo === 'cliente' ? 'text-slate-500' : 'text-[#70400f]') }}">
                            <span><b>{{ $i->remetente_nome ?: $i->usuario?->nome ?: 'Atendimento SISLOC' }}</b> {{ $i->tipo === 'nota_interna' ? '· nota interna' : '' }}</span>
                            <span class="flex items-center gap-1.5 whitespace-nowrap">
                                {{ $i->created_at->format('d/m/Y H:i') }}
                                @if ($i->edicoes->isNotEmpty())
                                    <span data-edit-indicator title="Mensagem editada. Texto anterior: {{ app(\App\Services\MensagemChamadoSanitizer::class)->textoSimples($i->edicoes->first()->mensagem_anterior) }}" aria-label="Mensagem editada" class="inline-flex cursor-help items-center"><i data-lucide="pencil-line" class="h-3 w-3"></i></span>
                                @endif
                            </span>
                        </div>
                        <div class="message-body whitespace-pre-line">{!! app(\App\Services\MensagemChamadoSanitizer::class)->sanitizar($i->mensagem) !!}</div>
                        @foreach ($i->anexos as $a)
                            <a class="mt-2 inline-flex items-center gap-1 text-xs underline {{ $i->tipo === 'nota_interna' ? 'text-amber-800 dark:text-amber-200' : ($i->remetente_tipo === 'cliente' ? 'text-orange-700' : 'text-[#70400f]') }}" href="{{ route('anexos.baixar', $a) }}"><i data-lucide="paperclip" class="h-3 w-3"></i> {{ $a->nome_original }}</a>
                        @endforeach
                        @can('responder', $chamado)
                            @if ($i->remetente_tipo !== 'cliente' && $i->usuario_id === auth()->id())
                                <details class="mt-2 border-t border-current/10 pt-2">
                                    <summary class="flex w-fit cursor-pointer list-none items-center gap-1 text-xs font-medium opacity-75 hover:opacity-100"><i data-lucide="pencil" class="h-3 w-3"></i> Corrigir mensagem</summary>
                                    <form method="POST" action="{{ route('chamados.interacoes.editar', [$chamado, $i]) }}" class="mt-2 space-y-2" data-editor-form>@csrf @method('PUT')
                                        <div data-message-editor contenteditable="true" role="textbox" aria-label="Corrigir mensagem" aria-multiline="true" class="min-h-20 w-full rounded-lg border border-current/20 bg-white/70 p-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/30 dark:bg-slate-950/50 dark:text-slate-100">{!! app(\App\Services\MensagemChamadoSanitizer::class)->sanitizar($i->mensagem) !!}</div>
                                        <input type="hidden" name="mensagem" data-message-html>
                                        <button class="btn-neutro"><i data-lucide="check" class="h-3.5 w-3.5"></i> Salvar correção</button>
                                    </form>
                                </details>
                            @endif
                        @endcan
                    </div>
                </li>
            @empty
                <li class="text-sm text-slate-500">Ainda não há interações.</li>
            @endforelse
        </ol>
        <button type="button" data-ir-ultimas-mensagens aria-label="Ir para as mensagens mais recentes" title="Ir para as mensagens mais recentes" class="absolute bottom-4 right-4 hidden h-11 w-11 items-center justify-center rounded-full border border-orange-200 bg-white text-orange-600 shadow-lg transition hover:-translate-y-0.5 hover:bg-orange-50 focus:outline-none focus:ring-4 focus:ring-orange-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-orange-300 dark:hover:bg-slate-800">
            <i data-lucide="arrow-down" class="h-5 w-5"></i>
        </button>
        </div>

        @can('responder', $chamado)
            @unless ($finalizado)
            <form method="POST" action="{{ route('chamados.responder', $chamado) }}" enctype="multipart/form-data" class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900" data-editor-form data-message-composer>
                @csrf
                @can('verNotasInternas', $chamado)
                    <div class="flex border-b border-slate-200 px-3 pt-2 dark:border-slate-700" role="tablist" aria-label="Tipo de mensagem">
                        <button type="button" class="border-b-2 border-orange-500 px-3 py-2 text-sm font-semibold text-orange-700 dark:text-orange-300" data-message-tab="resposta" aria-selected="true">Resposta</button>
                        <button type="button" class="border-b-2 border-transparent px-3 py-2 text-sm text-slate-500 hover:text-slate-800 dark:hover:text-slate-200" data-message-tab="nota_interna" aria-selected="false">Nota privada</button>
                    </div>
                    <input type="hidden" name="tipo" value="{{ old('tipo', 'resposta') }}" data-message-type>
                @else
                    <input type="hidden" name="tipo" value="resposta">
                @endcan
                <label for="mensagem-resposta" class="sr-only">Escreva sua mensagem</label>
                <div id="mensagem-resposta" data-message-editor contenteditable="true" role="textbox" aria-label="Escreva sua mensagem" aria-multiline="true" class="min-h-28 max-h-72 overflow-y-auto px-4 py-3 text-sm leading-6 text-slate-800 outline-none empty:before:text-slate-400 empty:before:content-[attr(data-placeholder)] dark:text-slate-100" data-placeholder="Escreva sua mensagem...">{!! app(\App\Services\MensagemChamadoSanitizer::class)->sanitizar(old('mensagem', '')) !!}</div>
                <input type="hidden" name="mensagem" data-message-html>
                @error('mensagem')<p class="px-4 pb-2 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 px-3 py-2 dark:border-slate-700">
                    <div class="flex items-center gap-1 text-slate-500 dark:text-slate-300">
                        <button type="button" data-editor-command="bold" aria-label="Negrito" title="Negrito" class="grid h-8 w-8 place-items-center rounded hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800"><b>B</b></button>
                        <button type="button" data-editor-command="italic" aria-label="Itálico" title="Itálico" class="grid h-8 w-8 place-items-center rounded hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800"><i>I</i></button>
                        <button type="button" data-editor-command="underline" aria-label="Sublinhado" title="Sublinhado" class="grid h-8 w-8 place-items-center rounded hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800"><u>U</u></button>
                        <button type="button" data-editor-link aria-label="Inserir link" title="Inserir link" class="grid h-8 w-8 place-items-center rounded hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800"><i data-lucide="link" class="h-4 w-4"></i></button>
                        <details class="relative">
                            <summary aria-label="Inserir emoji" title="Inserir emoji" class="grid h-8 w-8 cursor-pointer list-none place-items-center rounded hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800"><i data-lucide="smile" class="h-4 w-4"></i></summary>
                            <div class="absolute bottom-10 left-0 z-10 flex gap-1 rounded-lg border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                                @foreach (['🙂', '😊', '👍', '✅', '🙏', '🎉'] as $emoji)
                                    <button type="button" data-insert-emoji="{{ $emoji }}" class="grid h-8 w-8 place-items-center rounded hover:bg-orange-50 dark:hover:bg-slate-800" aria-label="Inserir {{ $emoji }}">{{ $emoji }}</button>
                                @endforeach
                            </div>
                        </details>
                        <button type="button" data-abrir-frases-rapidas aria-label="Frases rápidas" title="Frases rápidas" class="grid h-8 w-8 place-items-center rounded hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800"><i data-lucide="message-square-quote" class="h-4 w-4"></i></button>
                        <button type="button" data-anexar-arquivo aria-label="Anexar documentos" title="Anexar documentos" class="grid h-8 w-8 place-items-center rounded hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800"><i data-lucide="paperclip" class="h-4 w-4"></i></button>
                        <button type="button" data-anexar-imagem aria-label="Anexar imagem" title="Anexar imagem" class="grid h-8 w-8 place-items-center rounded hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800"><i data-lucide="image" class="h-4 w-4"></i></button>
                        <input id="anexos-resposta" type="file" name="anexos[]" multiple accept=".pdf,.png,.jpg,.jpeg,.txt,.doc,.docx,.xls,.xlsx,.csv,.zip" class="sr-only" data-anexos-resposta>
                    </div>
                    <button class="btn-primario"><i data-lucide="send" class="h-4 w-4"></i> Enviar</button>
                </div>
                <div class="border-t border-slate-100 px-3 py-2 dark:border-slate-800">
                    <ul data-lista-anexos class="hidden flex flex-wrap gap-2"></ul>
                    <p data-erro-anexos role="alert" class="hidden text-xs font-medium text-red-600 dark:text-red-400"></p>
                    <p class="mt-1 text-[10px] text-slate-400">Até 5 arquivos · máximo de 10 MB por arquivo · PDF, imagens, Office, TXT, CSV ou ZIP</p>
                </div>
            </form>
            <dialog data-frases-dialog class="w-[min(42rem,calc(100%-2rem))] max-h-[85vh] overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/40 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" @if ($errors->has('titulo') || $errors->has('conteudo')) open @endif>
                <div class="p-5">
                    <header class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <h3 class="font-semibold">Frases rápidas</h3>
                            <p class="mt-1 text-xs text-slate-500">Atalhos pessoais. Ao inserir, o texto continua editável na mensagem.</p>
                        </div>
                        <button type="button" data-fechar-frases aria-label="Fechar frases rápidas" title="Fechar" class="grid h-8 w-8 place-items-center rounded text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"><i data-lucide="x" class="h-4 w-4"></i></button>
                    </header>
                    @if ($errors->has('titulo') || $errors->has('conteudo'))
                        <p class="mb-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700" role="alert">{{ $errors->first('titulo') ?: $errors->first('conteudo') }}</p>
                    @endif
                    <div class="space-y-3">
                        @forelse ($frasesRapidas as $frase)
                            <article class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold">{{ $frase->titulo }}</p>
                                        <p class="mt-1 whitespace-pre-line break-words text-xs text-slate-500">{{ $frase->conteudo }}</p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1">
                                        <button type="button" data-inserir-frase data-frase-conteudo="{{ $frase->conteudo }}" class="btn-neutro"><i data-lucide="corner-down-left" class="h-3.5 w-3.5"></i> Inserir</button>
                                        <form method="POST" action="{{ route('chamados.frases-rapidas.destroy', [$chamado, $frase]) }}" onsubmit="return confirm('Excluir esta frase rápida?')">@csrf @method('DELETE')
                                            <button type="submit" aria-label="Excluir {{ $frase->titulo }}" title="Excluir frase" class="grid h-8 w-8 place-items-center rounded text-slate-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                                        </form>
                                    </div>
                                </div>
                                <details class="mt-3 border-t border-slate-100 pt-2 dark:border-slate-800">
                                    <summary class="w-fit cursor-pointer text-xs font-medium text-slate-500 hover:text-orange-700">Editar frase</summary>
                                    <form method="POST" action="{{ route('chamados.frases-rapidas.update', [$chamado, $frase]) }}" class="mt-3 grid gap-3">@csrf @method('PUT')
                                        <div><label class="rotulo" for="frase-titulo-{{ $frase->id }}">Nome do atalho</label><input class="campo" id="frase-titulo-{{ $frase->id }}" name="titulo" maxlength="80" value="{{ $frase->titulo }}" required></div>
                                        <div><label class="rotulo" for="frase-conteudo-{{ $frase->id }}">Texto</label><textarea class="campo" id="frase-conteudo-{{ $frase->id }}" name="conteudo" rows="3" maxlength="3000" required>{{ $frase->conteudo }}</textarea></div>
                                        <div><button class="btn-neutro"><i data-lucide="save" class="h-4 w-4"></i> Salvar alterações</button></div>
                                    </form>
                                </details>
                            </article>
                        @empty
                            <p class="rounded-lg bg-slate-50 p-3 text-sm text-slate-500 dark:bg-slate-800/60">Você ainda não salvou frases rápidas.</p>
                        @endforelse
                    </div>
                    <form method="POST" action="{{ route('chamados.frases-rapidas.store', $chamado) }}" class="mt-5 grid gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">@csrf
                        <h4 class="text-sm font-semibold">Criar frase rápida</h4>
                        <div><label class="rotulo" for="nova-frase-titulo">Nome do atalho</label><input class="campo" id="nova-frase-titulo" name="titulo" maxlength="80" value="{{ old('titulo') }}" required></div>
                        <div><label class="rotulo" for="nova-frase-conteudo">Texto reutilizável</label><textarea class="campo" id="nova-frase-conteudo" name="conteudo" rows="3" maxlength="3000" required>{{ old('conteudo') }}</textarea></div>
                        <div><button class="btn-primario"><i data-lucide="plus" class="h-4 w-4"></i> Salvar frase</button></div>
                    </form>
                </div>
            </dialog>
            @endunless
        @endcan
    </div>

    <div class="cartao">
        <h3 class="mb-3 font-semibold">Histórico de alterações</h3>
        <ul class="space-y-2 text-sm">
            @foreach ($chamado->historico as $h)
                <li class="flex justify-between gap-3 border-b border-slate-100 pb-2 dark:border-slate-800">
                    <span><b>{{ ucfirst($h->campo) }}</b>:
                        @if ($h->campo === 'chamado') {{ $h->valor_novo }}
                        @else {{ $h->valor_anterior ?: '—' }} → {{ $h->valor_novo ?: '—' }} @endif
                        <span class="text-slate-500">por {{ $h->usuario?->nome ?? 'sistema' }}</span></span>
                    <span class="shrink-0 text-xs text-slate-500">{{ $h->created_at->format('d/m/Y H:i') }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>

<aside class="space-y-6">
    <div class="cartao text-sm">
        <h3 class="mb-3 font-semibold">Solicitante</h3>
        <p class="font-medium">{{ $chamado->nomeSolicitante() }}</p>
        <p class="text-slate-500">{{ $chamado->emailSolicitante() ?? 'Solicitação pelo portal público' }}</p>
        @if ($chamado->empresa_nome)<p class="mt-1 text-slate-500">Empresa: {{ $chamado->empresa_nome }}</p>@endif
        @if ($chamado->empresa_cnpj)<p class="mt-1 text-slate-500">CNPJ: {{ $chamado->cnpjFormatado() }}</p>@endif
        <h3 class="mb-2 mt-5 font-semibold">Informações</h3>
        <dl class="space-y-1">
            <div class="flex justify-between"><dt class="text-slate-500">Departamento</dt><dd>{{ $chamado->departamento->nome }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Categoria</dt><dd>{{ $chamado->categoria?->nome ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Subcategoria</dt><dd>{{ $chamado->subcategoria?->nome ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Responsável</dt><dd>{{ $chamado->agente?->nome ?? 'Sem responsável' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Aberto em</dt><dd>{{ $chamado->created_at->format('d/m/Y H:i') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Atualizado em</dt><dd>{{ $chamado->updated_at->format('d/m/Y H:i') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Prazo</dt><dd class="{{ $chamado->estaAtrasado() ? 'font-semibold text-red-600' : '' }}">{{ $chamado->prazo_em?->format('d/m/Y H:i') }}</dd></div>
        </dl>
        <h3 class="mb-2 mt-5 font-semibold">Tempos</h3>
        <dl class="space-y-1">
            <div class="flex justify-between"><dt class="text-slate-500">Primeira resposta</dt><dd>{{ $tempoResposta ?? 'Aguardando' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">{{ $finalizado ? 'Tempo de resolução' : 'Tempo em aberto' }}</dt><dd>{{ $tempoTotal }}</dd></div>
        </dl>
    </div>

    @can('verNotasInternas', $chamado)
    <a href="{{ route('chamados.detalhamentos', $chamado) }}" class="cartao group flex items-center justify-between gap-3 transition hover:border-orange-300 hover:shadow-md">
        <span class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300"><i data-lucide="clipboard-list" class="h-5 w-5"></i></span>
            <span><span class="block font-semibold">Mais detalhamentos</span><span class="mt-1 block text-xs text-slate-500">Resumo automático, histórico do cliente e anotações internas</span></span>
        </span>
        <i data-lucide="arrow-up-right" class="h-4 w-4 shrink-0 text-slate-400 transition group-hover:text-orange-600"></i>
    </a>
    @endcan

    @unless ($finalizado)
    <div class="cartao space-y-4 text-sm">
        <h3 class="font-semibold">Ações</h3>
        @can('atribuir', $chamado)
            @if ($chamado->agente_id)
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm dark:border-emerald-900 dark:bg-emerald-950/30">
                    <p class="font-semibold text-emerald-800 dark:text-emerald-200">Responsável atribuído</p>
                    <p class="mt-1 text-slate-700 dark:text-slate-200">{{ $chamado->agente?->nome ?? 'Responsável atual' }}</p>
                    <p class="mt-1 text-xs text-slate-500">A atribuição não pode ser removida ou alterada por aqui. Para mudar o responsável, use o fluxo de transferência.</p>
                </div>
            @else
                <form method="POST" action="{{ route('chamados.atribuir', $chamado) }}" class="flex gap-2">@csrf
                    <select name="agente_id" class="campo" required>
                        <option value="">Selecione um responsável</option>
                        @foreach ($agentes as $a)<option value="{{ $a->id }}">{{ $a->nome }}</option>@endforeach
                    </select>
                    <button class="btn-neutro">Atribuir</button>
                </form>
            @endif
            <form method="POST" action="{{ route('chamados.status', $chamado) }}" class="space-y-2">@csrf
                <div class="flex gap-2">
                    <select name="status_id" class="campo" data-status-select>@foreach ($statusLista as $s)<option value="{{ $s->id }}" data-slug="{{ $s->slug }}" @selected(old('status_id', $chamado->status_id) == $s->id)>{{ $s->nome }}</option>@endforeach</select>
                    <button class="btn-neutro shrink-0">Alterar status</button>
                </div>
                <div class="hidden" data-status-justificativa>
                    <label class="rotulo" data-status-justificativa-label>O que está sendo aguardado?</label>
                    <textarea name="justificativa" class="campo" rows="2" minlength="5" maxlength="500" placeholder="Descreva a informação ou dependência necessária">{{ old('justificativa') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">Obrigatória para status de espera. A justificativa ficará no histórico do chamado.</p>
                </div>
            </form>
        @endcan
        @can('editar', $chamado)
            <form method="POST" action="{{ route('chamados.prioridade', $chamado) }}" class="flex gap-2">@csrf
                <select name="prioridade_id" class="campo">@foreach ($prioridades as $p)<option value="{{ $p->id }}" @selected($chamado->prioridade_id == $p->id)>{{ $p->nome }}</option>@endforeach</select>
                <button class="btn-neutro">Prioridade</button>
            </form>
        @endcan
        @can('transferir', $chamado)
            <form method="POST" action="{{ route('chamados.transferir', $chamado) }}" class="space-y-3 border-t border-slate-200 pt-4" onsubmit="return confirm('Transferir este atendimento?')">@csrf
                <h4 class="font-medium">Transferir atendimento</h4>
                <div><label class="rotulo">Departamento de destino</label><select name="departamento_id" class="campo" data-departamento-destino required>@foreach ($departamentos as $d)<option value="{{ $d->id }}" @selected($chamado->departamento_id == $d->id)>{{ $d->nome }}</option>@endforeach</select></div>
                <div><label class="rotulo">Novo responsável (opcional)</label><select name="agente_id" class="campo" data-agente-destino><option value="">Aguardando distribuição</option>@foreach ($agentes as $a)<option value="{{ $a->id }}" data-departamento="{{ $a->departamento_id }}" @selected($chamado->agente_id == $a->id)>{{ $a->nome }} · {{ $a->departamento?->nome ?? 'Sem departamento' }}</option>@endforeach</select></div>
                <div><label class="rotulo">Supervisor/administrador informado como autorizador</label><select name="autorizador_id" class="campo" required><option value="">Selecione quem autorizou</option>@foreach ($autorizadores as $pessoa)<option value="{{ $pessoa->id }}" @selected(old('autorizador_id') == $pessoa->id)>{{ $pessoa->nome }} · {{ $pessoa->perfil->nome }}</option>@endforeach</select><p class="mt-1 text-xs text-slate-500">A pessoa receberá uma notificação apenas para ciência. Não há etapa de aprovação.</p></div>
                <div><label class="rotulo">Prioridade para a equipe de destino</label><select name="prioridade_id" class="campo" required><option value="">Selecione a prioridade</option>@foreach ($prioridades as $p)<option value="{{ $p->id }}" @selected(old('prioridade_id', $chamado->prioridade_id) == $p->id)>{{ $p->nome }} · prazo de {{ $p->prazo_horas }}h</option>@endforeach</select></div>
                <div><label class="rotulo">Motivo da transferência</label><textarea name="motivo" class="campo" rows="2" minlength="5" maxlength="500" required></textarea></div>
                <div><label class="rotulo">Observações</label><textarea name="observacoes" class="campo" rows="2" maxlength="2000"></textarea></div>
                <button class="btn-primario w-full justify-center">TRANSFERIR ATENDIMENTO</button>
            </form>
        @endcan
        @if ($chamado->empresa_cnpj)
            @can('editar', $chamado)
                <form method="POST" action="{{ route('chamados.acesso.revogar', $chamado) }}" class="border-t border-slate-200 pt-3">@csrf
                    <button class="text-xs font-semibold text-red-600 hover:underline" onclick="return confirm('Revogar todos os links públicos ativos deste chamado?')">Revogar link público do cliente</button>
                </form>
            @endcan
        @endif
    </div>

    @can('finalizar', $chamado)
    <form method="POST" action="{{ route('chamados.finalizar', $chamado) }}" class="cartao space-y-3 text-sm">@csrf
        <h3 class="font-semibold">Finalizar atendimento</h3>
        @if ($finalizacaoBloqueada)
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-3 text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200" role="status">
                <p class="flex items-center gap-2 font-semibold"><i data-lucide="lock-keyhole" class="h-4 w-4"></i> Finalização temporariamente bloqueada</p>
                <p class="mt-1 text-xs leading-relaxed">Como você transferiu este chamado, a confirmação só será liberada quando o setor responsável devolver o atendimento atribuído a você.</p>
            </div>
        @endif
        <fieldset @disabled($finalizacaoBloqueada) class="space-y-3 disabled:opacity-55">
            <div><label class="rotulo">Motivo</label><select name="motivo_id" class="campo" required><option value="">Selecione…</option>
                @foreach ($motivos as $m)<option value="{{ $m->id }}" @selected(old('motivo_id') == $m->id)>{{ $m->nome }}</option>@endforeach</select></div>
            <div><label class="rotulo">Descrição da solução aplicada</label><textarea name="descricao_solucao" rows="3" class="campo" required>{{ old('descricao_solucao') }}</textarea></div>
            <div><label class="rotulo">Observações (opcional)</label><textarea name="observacoes" rows="2" class="campo">{{ old('observacoes') }}</textarea></div>
            <button class="btn-primario w-full justify-center" @disabled($finalizacaoBloqueada) onclick="return confirm('Confirmar a finalização do atendimento?')">Confirmar finalização</button>
        </fieldset>
    </form>
    @endcan
    @endunless
    @if ($finalizado)
        @can('chamados.reabrir')
            <form method="POST" action="{{ route('chamados.reabrir', $chamado) }}" class="cartao">@csrf
                <h3 class="font-semibold">Reabrir atendimento</h3>
                <p class="my-2 text-xs text-slate-500">A finalização anterior e todo o histórico serão preservados.</p>
                <button class="btn-neutro w-full justify-center" onclick="return confirm('Reabrir este atendimento?')">Reabrir chamado</button>
            </form>
        @endcan
    @endif
</aside>
</div>
<script>
    const conversation = document.getElementById('conversa-interna');
    const scrollToLatestButton = document.querySelector('[data-ir-ultimas-mensagens]');
    let lastInternalId = Number(conversation.dataset.ultimo);
    let lastInternalUpdate = conversation.dataset.atualizado;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const initializeEditorForm = (form) => {
        if (form.dataset.editorReady) return;
        form.dataset.editorReady = 'true';
        const editor = form.querySelector('[data-message-editor]');
        const messageInput = form.querySelector('[data-message-html]');
        form.addEventListener('submit', (event) => {
            messageInput.value = editor.innerHTML;
            if (!editor.textContent.trim()) {
                event.preventDefault();
                editor.focus();
            }
        });
        form.querySelectorAll('[data-editor-command]').forEach((button) => {
            button.addEventListener('click', () => {
                editor.focus();
        if (fileInput && fileList && fileError) {
            });
        });
        form.querySelector('[data-editor-link]')?.addEventListener('mousedown', (event) => event.preventDefault());
        form.querySelector('[data-editor-link]')?.addEventListener('click', () => {
            const url = window.prompt('Informe o endereço do link (https:// ou mailto:)');
            if (!url) return;
            editor.focus();
            document.execCommand('createLink', false, url);
        });
        form.querySelectorAll('[data-insert-emoji]').forEach((button) => {
            button.addEventListener('mousedown', (event) => event.preventDefault());
            button.addEventListener('click', () => {
                editor.focus();
                document.execCommand('insertText', false, button.dataset.insertEmoji);
                button.closest('details')?.removeAttribute('open');
            });
        });
    };
    document.querySelectorAll('[data-editor-form]').forEach(initializeEditorForm);
    const messageTypeInput = document.querySelector('[data-message-type]');
    document.querySelectorAll('[data-message-tab]').forEach((tab) => {
        const activateTab = () => {
            if (!messageTypeInput) return;
            messageTypeInput.value = tab.dataset.messageTab;
            document.querySelectorAll('[data-message-tab]').forEach((item) => {
                const active = item === tab;
                item.setAttribute('aria-selected', String(active));
                item.classList.toggle('border-orange-500', active);
                item.classList.toggle('text-orange-700', active);
                item.classList.toggle('dark:text-orange-300', active);
                item.classList.toggle('border-transparent', !active);
                item.classList.toggle('text-slate-500', !active);
            });
        };
        tab.addEventListener('click', activateTab);
        if (tab.dataset.messageTab === messageTypeInput?.value) activateTab();
    });
    const frasesDialog = document.querySelector('[data-frases-dialog]');
    const composerEditor = document.querySelector('[data-message-composer] [data-message-editor]');
    let savedMessageSelection = null;
    const rememberMessageSelection = () => {
        const selection = window.getSelection();
        if (selection?.rangeCount && composerEditor?.contains(selection.anchorNode)) {
            savedMessageSelection = selection.getRangeAt(0).cloneRange();
        }
    };
    const abrirFrases = document.querySelector('[data-abrir-frases-rapidas]');
    abrirFrases?.addEventListener('mousedown', rememberMessageSelection);
    abrirFrases?.addEventListener('click', () => frasesDialog?.showModal());
    document.querySelector('[data-fechar-frases]')?.addEventListener('click', () => frasesDialog?.close());
    frasesDialog?.querySelectorAll('[data-inserir-frase]').forEach((button) => {
        button.addEventListener('mousedown', (event) => event.preventDefault());
        button.addEventListener('click', () => {
            if (!composerEditor) return;
            composerEditor.focus();
            const selection = window.getSelection();
            selection?.removeAllRanges();
            if (savedMessageSelection && composerEditor.contains(savedMessageSelection.startContainer)) {
                selection?.addRange(savedMessageSelection);
            } else {
                const range = document.createRange();
                range.selectNodeContents(composerEditor);
                range.collapse(false);
                selection?.addRange(range);
            }
            document.execCommand('insertText', false, button.dataset.fraseConteudo ?? '');
            savedMessageSelection = null;
            frasesDialog.close();
        });
    });
    document.querySelector('[data-message-composer]')?.querySelector('[data-message-editor]')?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
            event.preventDefault();
            event.currentTarget.closest('form').requestSubmit();
        }
    });
    const isConversationAtBottom = () => conversation.scrollHeight - conversation.scrollTop - conversation.clientHeight < 48;
    const updateScrollToLatestButton = () => {
        const hasOlderMessages = conversation.scrollHeight > conversation.clientHeight + 8;
        const showButton = hasOlderMessages && !isConversationAtBottom();
        scrollToLatestButton.classList.toggle('hidden', !showButton);
        scrollToLatestButton.classList.toggle('flex', showButton);
    };
    const scrollConversationToLatest = () => {
        conversation.style.scrollBehavior = 'auto';
        conversation.scrollTop = conversation.scrollHeight;
        conversation.style.removeProperty('scroll-behavior');
    };
    conversation.addEventListener('scroll', updateScrollToLatestButton, {passive: true});
    scrollToLatestButton.addEventListener('click', () => {
        scrollConversationToLatest();
        updateScrollToLatestButton();
    });
    scrollConversationToLatest();
    updateScrollToLatestButton();
    const updateInternalConversation = async () => {
        try {
            const query = new URLSearchParams({depois_de: lastInternalId, atualizadas_depois: lastInternalUpdate});
            const response = await fetch(conversation.dataset.url + '?' + query, {headers: {'Accept': 'application/json'}});
            if (!response.ok) throw new Error('Falha ao atualizar as mensagens.');
            const data = await response.json();
            const wasAtBottom = isConversationAtBottom();
            data.mensagens.forEach((item) => {
                const existing = conversation.querySelector(`[data-interacao-id="${item.id}"]`);
                if (existing) {
                    existing.querySelector('.message-body').innerHTML = item.mensagem_html;
                    existing.querySelectorAll('[data-message-editor]').forEach((editor) => { editor.innerHTML = item.mensagem_html; });
                    updateEditIndicator(existing, item);
                    return;
                }
                const row = document.createElement('li');
                row.dataset.interacaoId = item.id;
                row.className = 'flex ' + (item.cliente ? 'justify-start' : 'justify-end');
                const box = document.createElement('div');
                box.className = 'max-w-[92%] rounded-2xl p-3 text-sm shadow-sm sm:max-w-[82%] ' + (item.interna
                    ? 'border border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100'
                    : item.cliente
                        ? 'border border-slate-200 bg-white text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100'
                        : 'border border-[#ffb45c] bg-[#ffb45c] text-[#452600]');
                const header = document.createElement('div');
                header.className = 'mb-1 flex justify-between gap-5 text-xs ' + (item.interna ? 'text-amber-700 dark:text-amber-300' : item.cliente ? 'text-slate-500' : 'text-[#70400f]');
                const sender = document.createElement('b');
                sender.textContent = item.nome + (item.interna ? ' · nota interna' : '');
                const time = document.createElement('span');
                time.textContent = item.criada_em;
                time.className = 'flex items-center gap-1.5 whitespace-nowrap';
                header.append(sender, time);
                const body = document.createElement('p');
                body.className = 'message-body whitespace-pre-line';
                body.innerHTML = item.mensagem_html;
                box.append(header, body);
                item.anexos.forEach((file) => {
                    const link = document.createElement('a');
                    link.className = 'mt-2 inline-flex items-center gap-1 text-xs underline ' + (item.interna ? 'text-amber-800 dark:text-amber-200' : item.cliente ? 'text-orange-700' : 'text-[#70400f]');
                    link.href = file.url;
                    link.textContent = file.nome;
                    box.append(link);
                });
                addEditIndicator(time, item);
                if (item.editavel) box.append(createEditForm(item));
                row.append(box);
                conversation.append(row);
                lastInternalId = Math.max(lastInternalId, item.id);
            });
            lastInternalUpdate = data.verificado_em;
            if (data.mensagens.length && wasAtBottom) scrollConversationToLatest();
            updateScrollToLatestButton();
        } catch (error) {
            console.error(error);
        }
    };
    const addEditIndicator = (header, item) => {
        if (!item.editada) return;
        const indicator = document.createElement('span');
        indicator.dataset.editIndicator = '';
        indicator.className = 'inline-flex cursor-help items-center';
        indicator.title = `Mensagem editada. Texto anterior: ${item.edicao_anterior ?? ''}`;
        indicator.setAttribute('aria-label', 'Mensagem editada');
        const icon = document.createElement('i');
        icon.dataset.lucide = 'pencil-line';
        icon.className = 'h-3 w-3';
        indicator.append(icon);
        header.append(indicator);
        if (window.lucide) lucide.createIcons({nodes: [indicator]});
    };
    const updateEditIndicator = (row, item) => {
        const header = row.querySelector('.mb-1 > span:last-child');
        header?.querySelector('[data-edit-indicator]')?.remove();
        if (header) addEditIndicator(header, item);
    };
    const createEditForm = (item) => {
        const details = document.createElement('details');
        details.className = 'mt-2 border-t border-current/10 pt-2';
        const summary = document.createElement('summary');
        summary.className = 'flex w-fit cursor-pointer list-none items-center gap-1 text-xs font-medium opacity-75 hover:opacity-100';
        summary.textContent = 'Corrigir mensagem';
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = item.edicao_url;
        form.className = 'mt-2 space-y-2';
        form.dataset.editorForm = '';
        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = csrfToken;
        const method = document.createElement('input');
        method.type = 'hidden';
        method.name = '_method';
        method.value = 'PUT';
        const editor = document.createElement('div');
        editor.dataset.messageEditor = '';
        editor.contentEditable = 'true';
        editor.setAttribute('role', 'textbox');
        editor.setAttribute('aria-label', 'Corrigir mensagem');
        editor.setAttribute('aria-multiline', 'true');
        editor.className = 'min-h-20 w-full rounded-lg border border-current/20 bg-white/70 p-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/30 dark:bg-slate-950/50 dark:text-slate-100';
        editor.innerHTML = item.mensagem_html;
        const message = document.createElement('input');
        message.type = 'hidden';
        message.name = 'mensagem';
        message.dataset.messageHtml = '';
        const submit = document.createElement('button');
        submit.className = 'btn-neutro';
        submit.textContent = 'Salvar correção';
        form.append(token, method, editor, message, submit);
        details.append(summary, form);
        initializeEditorForm(form);
        return details;
    };
    window.setInterval(updateInternalConversation, 5000);
    const dept = document.querySelector('[data-departamento-destino]');
    const agent = document.querySelector('[data-agente-destino]');
    if (dept && agent) {
        const filterAgents = () => {
            Array.from(agent.options).forEach((option) => {
                if (!option.value) return;
                option.hidden = option.dataset.departamento !== dept.value;
                if (option.hidden && option.selected) agent.value = '';
            });
        };
        dept.addEventListener('change', filterAgents);
        filterAgents();
    }
    const fileInput = document.querySelector('[data-anexos-resposta]');
    const fileList = document.querySelector('[data-lista-anexos]');
    const fileError = document.querySelector('[data-erro-anexos]');
    if (fileInput && fileList && fileError) {
        let selectedFiles = Array.from(fileInput.files);
        const formatFileSize = (bytes) => bytes >= 1024 * 1024
            ? `${(bytes / (1024 * 1024)).toFixed(1)} MB`
            : `${Math.max(1, Math.round(bytes / 1024))} KB`;
        const updateFileInput = () => {
            const transfer = new DataTransfer();
            selectedFiles.forEach((file) => transfer.items.add(file));
            fileInput.files = transfer.files;
        };
        const renderFiles = () => {
            fileList.replaceChildren();
            fileList.classList.toggle('hidden', selectedFiles.length === 0);
            selectedFiles.forEach((file, index) => {
                const item = document.createElement('li');
                item.className = 'inline-flex max-w-full items-center gap-2 rounded-lg border border-orange-200 bg-white px-2.5 py-1.5 text-xs text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200';
                const details = document.createElement('span');
                details.className = 'min-w-0 truncate';
                details.textContent = `${file.name} · ${formatFileSize(file.size)}`;
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'shrink-0 rounded p-0.5 text-slate-400 hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-500/30';
                remove.setAttribute('aria-label', `Remover ${file.name}`);
                remove.innerHTML = '<i data-lucide="x" class="h-3.5 w-3.5"></i>';
                remove.addEventListener('click', () => {
                    selectedFiles = selectedFiles.filter((_, fileIndex) => fileIndex !== index);
                    updateFileInput();
                    renderFiles();
                });
                item.append(details, remove);
                fileList.append(item);
            });
            if (window.lucide) lucide.createIcons();
        };
        const acceptFiles = (files) => {
            const candidates = Array.from(files);
            const invalidSize = candidates.find((file) => file.size > 10 * 1024 * 1024);
            const invalidType = candidates.find((file) => !/\.(pdf|png|jpe?g|txt|docx?|xlsx?|csv|zip)$/i.test(file.name));
            if (candidates.length > 5) {
                fileError.textContent = 'Selecione no máximo 5 arquivos por mensagem.';
            } else if (invalidSize) {
                fileError.textContent = `${invalidSize.name} ultrapassa o limite de 10 MB.`;
            } else if (invalidType) {
                fileError.textContent = `${invalidType.name} tem um formato não permitido.`;
            } else {
                selectedFiles = candidates;
                updateFileInput();
                renderFiles();
                fileError.textContent = '';
            }
            if (fileError.textContent) updateFileInput();
            fileError.classList.toggle('hidden', !fileError.textContent);
        };
        fileInput.addEventListener('change', () => acceptFiles(fileInput.files));
        document.querySelector('[data-anexar-arquivo]')?.addEventListener('click', () => {
            fileInput.accept = '.pdf,.png,.jpg,.jpeg,.txt,.doc,.docx,.xls,.xlsx,.csv,.zip';
            fileInput.click();
        });
        document.querySelector('[data-anexar-imagem]')?.addEventListener('click', () => {
            fileInput.accept = 'image/png,image/jpeg';
            fileInput.click();
        });
        renderFiles();
    }
    const statusSelect = document.querySelector('[data-status-select]');
    const statusJustificativa = document.querySelector('[data-status-justificativa]');
    if (statusSelect && statusJustificativa) {
        const statusLabel = statusJustificativa.querySelector('[data-status-justificativa-label]');
        const statusReason = statusJustificativa.querySelector('textarea');
        const labels = {
            aguardando_usuario: 'Qual informação está sendo aguardada do solicitante?',
            pendente: 'Qual ação ou dependência está pendente?',
            aguardando_terceiros: 'Qual atividade ou retorno de terceiro está sendo aguardado?',
        };
        const atualizarJustificativa = () => {
            const slug = statusSelect.selectedOptions[0]?.dataset.slug;
            const obrigatoria = Object.prototype.hasOwnProperty.call(labels, slug);
            statusJustificativa.classList.toggle('hidden', !obrigatoria);
            statusReason.required = obrigatoria;
            if (obrigatoria) statusLabel.textContent = labels[slug];
        };
        statusSelect.addEventListener('change', atualizarJustificativa);
        atualizarJustificativa();
    }
    if (window.lucide) lucide.createIcons();
</script>
@endsection
