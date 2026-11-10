<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $chamado->numero }} · SISLOC ATENDIMENTO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js"></script>
    <style>
        body {
            background:
                radial-gradient(ellipse at 9% 12%, rgba(255, 121, 0, .14), transparent 27rem),
                radial-gradient(ellipse at 93% 48%, rgba(182, 112, 52, .12), transparent 24rem),
                #eee7de;
        }
        .portal-chat { box-shadow: 0 30px 80px -44px rgba(32, 33, 36, .3), 0 8px 28px -16px rgba(32, 33, 36, .16); }
        .chat-fundo {
            background-color: #f2e7d9;
            background-image:
                radial-gradient(ellipse at 12% 8%, rgba(255, 250, 243, .72), transparent 44%),
                radial-gradient(ellipse at 92% 88%, rgba(226, 177, 128, .22), transparent 38%),
                radial-gradient(rgba(126, 90, 58, .10) .7px, transparent .7px);
            background-size: auto, auto, 20px 20px;
            scrollbar-color: #d3cec6 transparent;
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; } }
    </style>
</head>
<body class="min-h-screen font-sans text-slate-900 antialiased">
<header class="sticky top-0 z-10 border-b border-[#e4d7c8] bg-[#faf5ee]/95 backdrop-blur">
    <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
        <a href="{{ route('portal.chamados.criar') }}" class="flex items-center gap-3" aria-label="SISLOC Atendimento">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-[#ff7900] text-white shadow-md shadow-orange-500/25">
                <svg viewBox="0 0 32 32" fill="currentColor" class="h-7 w-7" aria-hidden="true"><circle cx="16" cy="5" r="3"/><circle cx="16" cy="13" r="3"/><circle cx="16" cy="21" r="3"/><circle cx="16" cy="29" r="2"/><circle cx="5" cy="16" r="3"/><circle cx="13" cy="16" r="3"/><circle cx="21" cy="16" r="3"/><circle cx="29" cy="16" r="2"/><circle cx="8" cy="8" r="2.4"/><circle cx="24" cy="8" r="2.4"/><circle cx="8" cy="24" r="2.4"/><circle cx="24" cy="24" r="2.4"/></svg>
            </span>
            <span class="leading-none"><span class="block text-[21px] font-extrabold tracking-[-.055em] text-slate-900">sisloc<span class="text-[#ff7900]">®</span></span><span class="mt-1 block text-[9px] font-bold uppercase tracking-[.19em] text-[#ff7900]">central de atendimento</span></span>
        </a>
        <div class="flex items-center gap-3">
            <span class="hidden text-right sm:block"><span class="block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Protocolo</span><span class="font-mono text-sm font-bold text-slate-700">{{ $chamado->numero }}</span></span>
            <x-selo-status :cor="$chamado->status->cor" :texto="$chamado->status->nome" />
        </div>
    </div>
</header>
<main class="mx-auto max-w-5xl space-y-5 px-4 py-6 sm:py-8">
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#202124] via-[#28292d] to-[#3a291d] p-5 text-white shadow-xl shadow-slate-900/10 sm:p-7">
        <div class="pointer-events-none absolute -right-16 -top-24 h-72 w-72 rounded-full bg-orange-500/15 blur-3xl"></div>
        <div class="relative flex flex-wrap items-start justify-between gap-5">
            <div class="max-w-2xl">
                <p class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[.06] px-3 py-1.5 text-[10px] font-bold uppercase tracking-[.16em] text-orange-200">
                    <i data-lucide="headset" class="h-3.5 w-3.5"></i> Atendimento SISLOC
                </p>
                <h1 class="mt-4 text-2xl font-bold tracking-tight sm:text-3xl">{{ $chamado->assunto }}</h1>
                <p class="mt-2 text-sm text-slate-300">Acompanhe as atualizações e fale com nossa equipe nesta página segura.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="rounded-xl border border-white/10 bg-white/[.06] px-3 py-2">
                    <span class="block text-[9px] font-semibold uppercase tracking-wider text-slate-400">Protocolo</span>
                    <span class="mt-0.5 block font-mono text-sm font-bold">{{ $chamado->numero }}</span>
                </span>
                <span class="rounded-xl border border-white/10 bg-white/[.06] px-3 py-2">
                    <span class="block text-[9px] font-semibold uppercase tracking-wider text-slate-400">Situação</span>
                    <span class="mt-1 block"><x-selo-status :cor="$chamado->status->cor" :texto="$chamado->status->nome" /></span>
                </span>
            </div>
        </div>
        <div class="relative mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-white/10 pt-4 text-xs text-slate-300">
            <span class="inline-flex items-center gap-2"><i data-lucide="calendar-days" class="h-3.5 w-3.5 text-orange-300"></i>Aberto em {{ $chamado->created_at->format('d/m/Y H:i') }}</span>
            @if ($chamado->departamento)
                <span class="inline-flex items-center gap-2"><i data-lucide="building-2" class="h-3.5 w-3.5 text-orange-300"></i>{{ $chamado->departamento->nome }}</span>
            @endif
            @if ($chamado->categoria)
                <span class="inline-flex items-center gap-2"><i data-lucide="folder" class="h-3.5 w-3.5 text-orange-300"></i>{{ $chamado->categoria->nome }}</span>
            @endif
        </div>
    </section>
    @if ($historicoRecente->isNotEmpty())
        <details class="cartao group">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 [&::-webkit-details-marker]:hidden">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Chamados recentes da sua empresa</h2>
                    <p class="mt-1 text-xs text-slate-500">Apenas protocolos, assuntos, status e datas dos últimos 30 dias. As conversas dos outros chamados permanecem privadas.</p>
                </div>
                <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">{{ $historicoRecente->count() }}</span>
            </div>
                <i data-lucide="chevron-down" class="mb-3 h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180"></i>
            </summary>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($historicoRecente as $recente)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                        <div>
                            <span class="font-mono text-xs text-slate-500">{{ $recente->numero }}</span>
                            <p class="font-medium">{{ $recente->assunto }}</p>
                            <p class="text-xs text-slate-500">{{ $recente->categoria?->nome ?? 'Atendimento' }} · {{ $recente->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <x-selo-status :cor="$recente->status->cor" :texto="$recente->status->nome" />
                    </li>
                @endforeach
            </ul>
        </details>
    @endif
    <section class="cartao">
        <div class="flex items-center gap-2">
            <span class="grid h-8 w-8 place-items-center rounded-xl bg-orange-100 text-orange-700"><i data-lucide="file-text" class="h-4 w-4"></i></span>
            <h2 class="font-semibold">Resumo da solicitação</h2>
        </div>
        <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $chamado->descricao }}</p>
        @if ($chamado->funcionamento_esperado)
            <div class="mt-4 rounded-2xl border border-orange-100 bg-orange-50/70 p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-orange-800">Resultado esperado</p>
                <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $chamado->funcionamento_esperado }}</p>
            </div>
        @endif
    </section>
    @if (session('sucesso'))<p class="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('sucesso') }}</p>@endif
    @if ($errors->any())<ul class="rounded-xl bg-red-50 p-3 text-sm text-red-800">@foreach ($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>@endif
    <section class="portal-chat overflow-hidden rounded-2xl border border-[#dfceba] bg-[#f6ecdf] sm:rounded-3xl">
        <header class="relative flex items-center gap-3 overflow-hidden bg-[#222326] px-4 py-3.5 text-white sm:px-6 sm:py-4">
            <span class="absolute inset-y-0 left-0 w-1 bg-[#ff7900]"></span>
            <span class="relative grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-[#ff7900] text-white">
                <svg viewBox="0 0 32 32" fill="currentColor" class="h-7 w-7" aria-hidden="true"><circle cx="16" cy="5" r="3"/><circle cx="16" cy="13" r="3"/><circle cx="16" cy="21" r="3"/><circle cx="16" cy="29" r="2"/><circle cx="5" cy="16" r="3"/><circle cx="13" cy="16" r="3"/><circle cx="21" cy="16" r="3"/><circle cx="29" cy="16" r="2"/><circle cx="8" cy="8" r="2.4"/><circle cx="24" cy="8" r="2.4"/><circle cx="8" cy="24" r="2.4"/><circle cx="24" cy="24" r="2.4"/></svg>
                <span class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-[#222326] bg-emerald-400"></span>
            </span>
            <div class="min-w-0 flex-1">
                <h2 class="font-semibold tracking-tight">Conversa do atendimento</h2>
                <p class="mt-0.5 flex items-center gap-1.5 text-xs text-slate-300"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span><span data-status role="status" aria-live="polite">Conectando para atualizar</span></p>
            </div>
            @if ($mensagensNaoLidas > 0)<span class="rounded-full bg-white/15 px-2.5 py-1 text-xs font-semibold text-white">{{ $mensagensNaoLidas }} nova(s)</span>@endif
        </header>
        <div class="relative">
        <ol id="conversa" data-ultimo="{{ $interacoes->last()?->id ?? 0 }}" data-atualizado="{{ now()->subSeconds(2)->toDateTimeString() }}" class="chat-fundo flex h-[50vh] min-h-72 flex-col gap-3 overflow-y-auto overscroll-contain scroll-smooth px-3 py-5 sm:h-[55vh] sm:min-h-96 sm:gap-4 sm:px-6 sm:py-6">
            @forelse ($interacoes as $interacao)
            <li data-interacao-id="{{ $interacao->id }}" class="flex max-w-[94%] items-end gap-2 sm:max-w-[82%] {{ $interacao->remetente_tipo === 'cliente' ? 'self-end flex-row-reverse' : 'self-start' }}">
                    @if ($interacao->remetente_tipo !== 'cliente')
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-[#ff7900] text-white"><svg viewBox="0 0 32 32" fill="currentColor" class="h-5 w-5" aria-hidden="true"><circle cx="16" cy="5" r="3"/><circle cx="16" cy="13" r="3"/><circle cx="16" cy="21" r="3"/><circle cx="16" cy="29" r="2"/><circle cx="5" cy="16" r="3"/><circle cx="13" cy="16" r="3"/><circle cx="21" cy="16" r="3"/><circle cx="29" cy="16" r="2"/><circle cx="8" cy="8" r="2.4"/><circle cx="24" cy="8" r="2.4"/><circle cx="8" cy="24" r="2.4"/><circle cx="24" cy="24" r="2.4"/></svg></span>
                    @endif
                    <div class="min-w-0 rounded-2xl px-4 py-3 text-sm leading-6 shadow-[0_2px_8px_rgba(15,23,42,.08)] {{ $interacao->remetente_tipo === 'cliente' ? 'rounded-br-md border border-[#e8d2ba] bg-gradient-to-br from-[#fffaf3] via-[#fbf1e5] to-[#f2e1ce] text-slate-800 shadow-[0_10px_26px_rgba(124,76,32,.12)]' : 'rounded-bl-md border border-[#e8b36e] bg-gradient-to-br from-[#ffc77e] to-[#ffb45c] text-[#452600] shadow-[0_10px_26px_rgba(124,76,32,.14)]' }}">
                        <div class="mb-1 flex items-center justify-between gap-5 text-[10px] {{ $interacao->remetente_tipo === 'cliente' ? 'text-slate-400' : 'text-[#70400f]' }}"><b class="font-semibold {{ $interacao->remetente_tipo === 'cliente' ? 'text-slate-600' : 'text-[#452600]' }}">{{ $interacao->remetente_tipo === 'cliente' ? 'Você' : ($interacao->remetente_nome ?: $interacao->usuario?->nome ?: 'Atendimento SISLOC') }}</b><span class="flex items-center gap-1.5"><time>{{ $interacao->created_at->format('d/m/Y H:i') }}</time>@if ($interacao->edicoes->isNotEmpty())<span data-edit-indicator title="Mensagem editada. Texto anterior: {{ app(\App\Services\MensagemChamadoSanitizer::class)->textoSimples($interacao->edicoes->first()->mensagem_anterior) }}" aria-label="Mensagem editada" class="inline-flex cursor-help items-center"><i data-lucide="pencil-line" class="h-3 w-3"></i></span>@endif</span></div>
                        <div class="message-body whitespace-pre-line break-words">{!! app(\App\Services\MensagemChamadoSanitizer::class)->sanitizar($interacao->mensagem) !!}</div>
                        @foreach ($interacao->anexos as $anexo)<a class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold underline {{ $interacao->remetente_tipo === 'cliente' ? 'text-orange-700' : 'text-[#70400f]' }}" href="{{ route('portal.chamados.anexos', [$token, $anexo]) }}"><svg viewBox="0 0 16 16" fill="none" class="h-3.5 w-3.5" aria-hidden="true"><path d="m6 8.5 3.8-3.8a2 2 0 0 1 2.8 2.8L7.3 12.6a3 3 0 0 1-4.2-4.2l5.3-5.3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>{{ $anexo->nome_original }}</a>@endforeach
                    </div>
                </li>
            @empty
                <li class="self-start rounded-2xl border border-white/80 bg-white px-4 py-3 text-sm text-slate-600 shadow-sm">Ainda não há mensagens. Nossa equipe responderá por aqui.</li>
            @endforelse
        </ol>
        <button type="button" data-ir-ultimas-mensagens aria-label="Ir para as mensagens mais recentes" title="Ir para as mensagens mais recentes" class="absolute bottom-4 right-4 hidden h-11 w-11 items-center justify-center rounded-full border border-orange-200 bg-white text-orange-600 shadow-lg transition hover:-translate-y-0.5 hover:bg-orange-50 focus:outline-none focus:ring-4 focus:ring-orange-500/20">
            <svg viewBox="0 0 20 20" fill="none" class="h-5 w-5" aria-hidden="true"><path d="M10 3.5v13m0 0 5-5m-5 5-5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        </div>
        @if (! $chamado->estaFinalizado())
            <form method="POST" action="{{ route('portal.chamados.responder', $token) }}" enctype="multipart/form-data" class="border-t border-slate-200 bg-white px-3 py-3.5 sm:px-6 sm:py-4" data-responder>
                @csrf
                <label for="mensagem" class="mb-2 block text-xs font-semibold text-slate-600">Escreva sua mensagem</label>
                <div class="flex items-end gap-2 rounded-2xl border border-[#dfcbb4] bg-gradient-to-br from-[#f4e8d9] via-[#fbf4eb] to-[#f0dfcc] p-2 shadow-inner transition focus-within:border-[#ff9a3c] focus-within:ring-4 focus-within:ring-orange-500/15">
                    <label class="grid h-11 w-11 shrink-0 cursor-pointer place-items-center rounded-xl text-slate-500 transition hover:bg-orange-50 hover:text-[#e86e00]" title="Anexar arquivos">
                        <svg viewBox="0 0 20 20" fill="none" class="h-5 w-5" aria-hidden="true"><path d="m7.5 10.8 5-5a2.65 2.65 0 0 1 3.75 3.75l-6.9 6.9a4 4 0 0 1-5.66-5.66l6.9-6.9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        <input type="file" name="anexos[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv" class="sr-only" data-anexos>
                    </label>
                    <textarea id="mensagem" name="mensagem" rows="1" maxlength="10000" placeholder="Digite sua mensagem..." class="min-h-11 max-h-36 min-w-0 flex-1 resize-y border-0 bg-transparent px-2 py-2.5 text-sm text-slate-800 outline-none placeholder:text-slate-400 focus:ring-0" required>{{ old('mensagem') }}</textarea>
                    <button class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-[#ff7900] text-white shadow-md shadow-orange-500/20 transition hover:bg-[#e86e00] focus:outline-none focus:ring-4 focus:ring-orange-500/20" aria-label="Enviar mensagem">
                        <svg viewBox="0 0 20 20" fill="none" class="h-5 w-5" aria-hidden="true"><path d="m18 2-7.5 16-2.2-6.3L2 9.5 18 2Zm0 0L8.3 11.7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
                <div class="mt-2 flex flex-wrap items-center justify-between gap-2 px-1">
                    <span class="text-[10px] text-slate-500" data-arquivos-label>Nenhum arquivo anexado</span>
                    <span class="text-[10px] text-slate-400">Conversa protegida · atualizada automaticamente</span>
                </div>
            </form>
        @else
            <p class="border-t border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Este atendimento foi finalizado. O histórico permanece disponível para consulta.</p>
        @endif
    </section>
</main>
<script>
    const list = document.getElementById('conversa');
    let lastId = Number(list.dataset.ultimo);
    let lastUpdated = list.dataset.atualizado;
    const statusLabel = document.querySelector('[data-status]');
    const scrollToLatestButton = document.querySelector('[data-ir-ultimas-mensagens]');
    const isConversationAtBottom = () => list.scrollHeight - list.scrollTop - list.clientHeight < 48;
    const updateScrollToLatestButton = () => {
        const hasOlderMessages = list.scrollHeight > list.clientHeight + 8;
        const showButton = hasOlderMessages && !isConversationAtBottom();
        scrollToLatestButton.classList.toggle('hidden', !showButton);
        scrollToLatestButton.classList.toggle('flex', showButton);
    };
    const scrollConversationToLatest = () => {
        list.style.scrollBehavior = 'auto';
        list.scrollTop = list.scrollHeight;
        list.style.removeProperty('scroll-behavior');
    };
    list.addEventListener('scroll', updateScrollToLatestButton, {passive: true});
    scrollToLatestButton.addEventListener('click', () => {
        scrollConversationToLatest();
        updateScrollToLatestButton();
    });
    scrollConversationToLatest();
    updateScrollToLatestButton();
    const refresh = async () => {
        try {
            const query = new URLSearchParams({depois_de: lastId, atualizadas_depois: lastUpdated});
            const response = await fetch(@json(route('portal.chamados.mensagens', $token)) + '?' + query, {headers: {'Accept': 'application/json'}});
            if (!response.ok) throw new Error('Não foi possível atualizar a conversa.');
            const data = await response.json();
            const wasAtBottom = isConversationAtBottom();
            statusLabel.textContent = 'Conversa atualizada';
            data.mensagens.forEach((item) => {
                const existing = list.querySelector(`[data-interacao-id="${item.id}"]`);
                if (existing) {
                    existing.querySelector('.message-body').innerHTML = item.mensagem_html;
                    updatePortalEditIndicator(existing, item);
                    return;
                }
                const row = document.createElement('li');
                row.dataset.interacaoId = item.id;
                row.className = 'flex max-w-[94%] items-end gap-2 sm:max-w-[82%] ' + (item.cliente ? 'self-end flex-row-reverse' : 'self-start');
                if (!item.cliente) {
                    const avatar = document.createElement('span');
                    avatar.className = 'grid h-7 w-7 shrink-0 place-items-center rounded-full bg-[#ff7900] text-white';
                    avatar.innerHTML = '<svg viewBox="0 0 32 32" fill="currentColor" class="h-5 w-5" aria-hidden="true"><circle cx="16" cy="5" r="3"/><circle cx="16" cy="13" r="3"/><circle cx="16" cy="21" r="3"/><circle cx="16" cy="29" r="2"/><circle cx="5" cy="16" r="3"/><circle cx="13" cy="16" r="3"/><circle cx="21" cy="16" r="3"/><circle cx="29" cy="16" r="2"/><circle cx="8" cy="8" r="2.4"/><circle cx="24" cy="8" r="2.4"/><circle cx="8" cy="24" r="2.4"/><circle cx="24" cy="24" r="2.4"/></svg>';
                    row.append(avatar);
                }
                const bubble = document.createElement('div');
                bubble.className = 'min-w-0 rounded-2xl px-4 py-3 text-sm leading-6 shadow-[0_2px_8px_rgba(15,23,42,.08)] ' + (item.cliente
                    ? 'rounded-br-md border border-[#e8d2ba] bg-gradient-to-br from-[#fffaf3] via-[#fbf1e5] to-[#f2e1ce] text-slate-800 shadow-[0_10px_26px_rgba(124,76,32,.12)]'
                    : 'rounded-bl-md border border-[#e8b36e] bg-gradient-to-br from-[#ffc77e] to-[#ffb45c] text-[#452600] shadow-[0_10px_26px_rgba(124,76,32,.14)]');
                const header = document.createElement('div');
                header.className = 'mb-1 flex justify-between gap-5 text-[10px] ' + (item.cliente ? 'text-slate-400' : 'text-[#70400f]');
                const name = document.createElement('b');
                name.className = 'font-semibold ' + (item.cliente ? 'text-slate-600' : 'text-[#452600]');
                name.textContent = item.cliente ? 'Você' : item.nome;
                const time = document.createElement('time');
                time.textContent = item.criada_em;
                header.append(name, time);
                addPortalEditIndicator(header, item);
                const body = document.createElement('p');
                body.className = 'message-body whitespace-pre-line break-words';
                body.innerHTML = item.mensagem_html;
                bubble.append(header, body);
                item.anexos.forEach((file) => {
                    const link = document.createElement('a');
                    link.className = 'mt-2 inline-flex text-xs font-semibold underline ' + (item.cliente ? 'text-orange-700' : 'text-[#70400f]');
                    link.href = file.url;
                    link.textContent = file.nome;
                    bubble.append(link);
                });
                row.append(bubble);
                list.append(row);
                lastId = Math.max(lastId, item.id);
            });
            lastUpdated = data.verificado_em;
            if (data.mensagens.length && wasAtBottom) scrollConversationToLatest();
            updateScrollToLatestButton();
            if (data.finalizado) {
                statusLabel.textContent = 'Atendimento finalizado';
                document.querySelector('[data-responder]')?.remove();
            }
        } catch (error) {
            statusLabel.textContent = 'Sem conexão. Tentando novamente…';
        }
    };
    const addPortalEditIndicator = (header, item) => {
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
    const updatePortalEditIndicator = (row, item) => {
        const header = row.querySelector('.mb-1');
        header?.querySelector('[data-edit-indicator]')?.remove();
        if (header) addPortalEditIndicator(header, item);
    };
    window.setInterval(refresh, 5000);
    const fileInput = document.querySelector('[data-anexos]');
    const fileLabel = document.querySelector('[data-arquivos-label]');
    fileInput?.addEventListener('change', () => {
        const files = Array.from(fileInput.files || []);
        fileLabel.textContent = files.length ? files.map((file) => file.name).join(', ') : 'Nenhum arquivo anexado';
    });
    if (window.lucide) lucide.createIcons();
</script>
</body>
</html>
