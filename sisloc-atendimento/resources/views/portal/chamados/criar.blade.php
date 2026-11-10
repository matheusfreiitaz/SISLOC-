<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat de atendimento · SISLOC ATENDIMENTO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js"></script>
    <style>
        :root { --sisloc-orange: #ff7900; --sisloc-ink: #202124; }
        body {
            background: #f3f5f6;
        }
        .portal-chat { box-shadow: 0 18px 48px -36px rgba(32, 33, 36, .4), 0 2px 8px rgba(32, 33, 36, .06); }
        .chat-fundo {
            background-color: #f7f8f9;
            background-image:
                radial-gradient(rgba(71, 85, 105, .055) .7px, transparent .7px);
            background-size: 22px 22px;
            scrollbar-color: #d3cec6 transparent;
        }
        .chat-compositor {
            background: #fff;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .06);
        }
        .chat-bolha { animation: chat-entrar .2s ease-out both; }
        .chat-texto { accent-color: var(--sisloc-orange); }
        .chat-texto:focus { border-color: var(--sisloc-orange); box-shadow: 0 0 0 3px rgba(255, 121, 0, .15); }
        .marca-sisloc { letter-spacing: -.055em; }
        .typing-dot { animation: typing 1.1s infinite ease-in-out; }
        .typing-dot:nth-child(2) { animation-delay: .15s; }
        .typing-dot:nth-child(3) { animation-delay: .3s; }
        @keyframes chat-entrar {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes typing { 0%, 60%, 100% { opacity: .35; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-3px); } }
        @media (prefers-reduced-motion: reduce) { .chat-bolha, .typing-dot { animation: none; } }
    </style>
</head>
<body class="flex h-[100dvh] flex-col overflow-hidden font-sans text-slate-900 antialiased">
<header class="sticky top-0 z-20 shrink-0 border-b border-slate-200 bg-white/95 backdrop-blur-xl">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5 sm:px-6 lg:px-8">
        <a href="{{ route('portal.chamados.criar') }}" class="group flex items-center gap-3" aria-label="SISLOC Atendimento">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-[#ff7900] text-white shadow-md shadow-orange-500/25 transition group-hover:-rotate-3 group-hover:scale-105">
                <svg viewBox="0 0 32 32" fill="currentColor" class="h-7 w-7" aria-hidden="true">
                    <circle cx="16" cy="5" r="3"/><circle cx="16" cy="13" r="3"/><circle cx="16" cy="21" r="3"/><circle cx="16" cy="29" r="2"/>
                    <circle cx="5" cy="16" r="3"/><circle cx="13" cy="16" r="3"/><circle cx="21" cy="16" r="3"/><circle cx="29" cy="16" r="2"/>
                    <circle cx="8" cy="8" r="2.4"/><circle cx="24" cy="8" r="2.4"/><circle cx="8" cy="24" r="2.4"/><circle cx="24" cy="24" r="2.4"/>
                </svg>
            </span>
            <span class="leading-none"><span class="marca-sisloc block text-[21px] font-extrabold text-slate-900">sisloc<span class="text-[#ff7900]">®</span></span><span class="mt-1 block text-[9px] font-bold uppercase tracking-[.19em] text-[#ff7900]">central de atendimento</span></span>
        </a>
        <div class="flex items-center gap-2">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-orange-200 hover:bg-orange-50 hover:text-slate-900 sm:text-sm">
            <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4 text-[#ff7900]" aria-hidden="true"><path d="M10 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.5 7v-1a5.5 5.5 0 0 1 11 0v1m1-12h2m-1-1v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            Acesso da equipe
        </a>
        </div>
    </div>
</header>
<main class="mx-auto flex min-h-0 w-full max-w-5xl flex-1 justify-center overflow-hidden px-2 py-2 sm:px-5 sm:py-3">
    <section class="portal-chat flex min-h-0 w-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white">
        <header class="relative flex shrink-0 items-center gap-3 border-b border-slate-800 bg-[#222326] px-4 py-3 text-white sm:px-5">
            <span class="absolute inset-y-0 left-0 w-1 bg-[#ff7900]"></span>
            <span class="relative grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[#ff7900] text-white">
                <svg viewBox="0 0 32 32" fill="currentColor" class="h-6 w-6" aria-hidden="true"><circle cx="16" cy="5" r="3"/><circle cx="16" cy="13" r="3"/><circle cx="16" cy="21" r="3"/><circle cx="16" cy="29" r="2"/><circle cx="5" cy="16" r="3"/><circle cx="13" cy="16" r="3"/><circle cx="21" cy="16" r="3"/><circle cx="29" cy="16" r="2"/><circle cx="8" cy="8" r="2.4"/><circle cx="24" cy="8" r="2.4"/><circle cx="8" cy="24" r="2.4"/><circle cx="24" cy="24" r="2.4"/></svg>
            </span>
            <div class="min-w-0 flex-1">
                <h1 class="text-sm font-bold tracking-tight sm:text-base">Abrir chamado</h1>
                <p class="mt-0.5 flex items-center gap-1.5 text-[11px] text-slate-300"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Assistente SISLOC</p>
            </div>
            <button type="button" data-abrir-ajuda class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-white/10 bg-white/[.06] text-slate-200 transition hover:bg-white/10" aria-label="Ajuda e segurança" title="Ajuda e segurança"><i data-lucide="circle-help" class="h-4 w-4"></i></button>
        </header>

        <div class="sticky top-0 z-10 shrink-0 border-b border-slate-200 bg-white px-4 py-2.5 sm:px-5">
            <div class="mb-2 flex items-center justify-between gap-3">
                <p id="chat-progresso" class="text-xs font-semibold text-slate-600" aria-live="polite">Preparando atendimento</p>
                <span id="chat-progresso-percentual" class="text-[11px] font-semibold tabular-nums text-slate-500">0%</span>
            </div>
            <div class="h-1 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-label="Progresso do formulário" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                <div id="chat-progresso-inline" class="h-full w-0 rounded-full bg-[#ff7900] transition-all duration-300"></div>
            </div>
        </div>

        <div id="chat-mensagens" class="chat-fundo flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto px-4 py-5 sm:gap-4 sm:px-6 sm:py-6" aria-live="polite" aria-label="Conversa de abertura de chamado">
            <div class="chat-bolha flex max-w-[94%] items-end gap-2 self-start sm:max-w-[82%]">
                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-[#ff7900] text-white">
                    <svg viewBox="0 0 32 32" fill="currentColor" class="h-5 w-5" aria-hidden="true"><circle cx="16" cy="5" r="3"/><circle cx="16" cy="13" r="3"/><circle cx="16" cy="21" r="3"/><circle cx="16" cy="29" r="2"/><circle cx="5" cy="16" r="3"/><circle cx="13" cy="16" r="3"/><circle cx="21" cy="16" r="3"/><circle cx="29" cy="16" r="2"/><circle cx="8" cy="8" r="2.4"/><circle cx="24" cy="8" r="2.4"/><circle cx="8" cy="24" r="2.4"/><circle cx="24" cy="24" r="2.4"/></svg>
                </span>
                <div class="rounded-xl rounded-bl-sm border border-slate-200 bg-white px-4 py-3 text-sm leading-6 text-slate-700 shadow-sm sm:px-5">
                    Olá! Vou ajudar você a registrar seu atendimento. Farei uma pergunta por vez. Depois de gerar o protocolo, você continuará a conversa com nossa equipe pelo link seguro enviado ao seu e-mail.
                    <time class="mt-1.5 block text-right text-[10px] font-medium text-slate-400">{{ now()->format('H:i') }}</time>
                </div>
            </div>
            @if ($errors->any())
                <div class="max-w-[94%] self-start rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-800 sm:max-w-[82%]">
                    Não consegui registrar ainda. Confira: {{ $errors->first() }} Vamos revisar suas respostas.
                </div>
            @endif
        </div>

        <form id="portal-chat-form" method="POST" action="{{ route('portal.chamados.armazenar') }}" enctype="multipart/form-data" class="relative z-10 shrink-0 border-t border-slate-200 bg-white px-3 py-3 sm:px-5 sm:py-4">
            @csrf
            <div class="mb-2 flex items-center justify-between gap-3">
                <p id="chat-pergunta" class="min-w-0 text-xs font-semibold text-slate-700" aria-live="polite">Carregando conversa…</p>
                <span class="shrink-0 text-[10px] font-medium text-slate-400">Obrigatório indicado</span>
            </div>
            <div id="chat-controles" class="chat-compositor flex items-end gap-2 rounded-xl border border-slate-200 p-2 transition focus-within:border-[#ff9a3c] focus-within:ring-4 focus-within:ring-orange-500/15">
                <div id="chat-resposta" class="min-w-0 flex-1"></div>
                <button id="chat-enviar" type="button" class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-[#ff7900] text-white shadow-md shadow-orange-500/20 transition hover:bg-[#e86e00] focus:outline-none focus:ring-4 focus:ring-orange-500/20 disabled:cursor-not-allowed disabled:opacity-50" aria-label="Enviar resposta" title="Enviar resposta · Enter">
                    <svg viewBox="0 0 20 20" fill="none" class="h-5 w-5" aria-hidden="true"><path d="m18 2-7.5 16-2.2-6.3L2 9.5 18 2Zm0 0L8.3 11.7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
            <div id="chat-campos-ocultos" class="hidden" aria-hidden="true"></div>
            <div class="mt-2 flex items-center justify-between gap-3 border-t border-slate-100 pt-2">
                <p class="text-[10px] leading-4 text-slate-500">Enter envia · Shift + Enter quebra linha</p>
                <p class="inline-flex items-center gap-1.5 text-[10px] font-medium text-slate-500"><i data-lucide="shield-check" class="h-3 w-3 text-emerald-600"></i>Ambiente seguro e criptografado</p>
            </div>
            <button id="chat-finalizar" type="submit" class="mt-3 hidden w-full items-center justify-center gap-2 rounded-lg bg-[#ff7900] px-4 py-3 font-semibold text-white shadow-md shadow-orange-500/20 transition hover:bg-[#e86e00] focus:outline-none focus:ring-4 focus:ring-orange-500/20">
                <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Confirmar e ABRIR CHAMADO
            </button>
        </form>
    </section>
    <dialog id="ajuda-atendimento" class="w-[min(30rem,calc(100%-2rem))] rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/40" aria-labelledby="ajuda-atendimento-titulo">
        <div class="p-5 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div><h2 id="ajuda-atendimento-titulo" class="font-semibold">Ajuda e segurança</h2><p class="mt-1 text-sm text-slate-500">A abertura acontece em três etapas.</p></div>
                <button type="button" data-fechar-ajuda aria-label="Fechar ajuda" title="Fechar" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><i data-lucide="x" class="h-4 w-4"></i></button>
            </div>
            <ol class="mt-5 space-y-4 text-sm">
                <li class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-orange-100 text-xs font-bold text-orange-700">1</span><span><b>Conte o que aconteceu</b><span class="mt-0.5 block text-xs text-slate-500">Responda às perguntas do assistente para direcionarmos o atendimento.</span></span></li>
                <li class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-orange-100 text-xs font-bold text-orange-700">2</span><span><b>Receba seu protocolo</b><span class="mt-0.5 block text-xs text-slate-500">A equipe responsável receberá sua solicitação.</span></span></li>
                <li class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-orange-100 text-xs font-bold text-orange-700">3</span><span><b>Acompanhe pelo e-mail</b><span class="mt-0.5 block text-xs text-slate-500">Use o link seguro enviado para continuar a conversa.</span></span></li>
            </ol>
            <div class="mt-5 flex gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs leading-5 text-emerald-900"><i data-lucide="shield-check" class="mt-0.5 h-4 w-4 shrink-0"></i><span>Ambiente seguro. Nunca envie senhas ou dados bancários.</span></div>
        </div>
    </dialog>
</main>
@php
    $configuracaoChat = $campos->map(fn ($campo) => [
        'chave' => $campo->chave,
        'rotulo' => $campo->rotulo,
        'descricao' => $campo->descricao,
        'tipo' => $campo->tipo,
        'obrigatorio' => $campo->obrigatorio,
        'tamanho_maximo' => $campo->tamanho_maximo,
        'opcoes' => $campo->opcoes,
    ])->values();
    $categoriasChat = $categorias->map(fn ($categoria) => [
        'id' => $categoria->id,
        'nome' => $categoria->nome,
    ])->values();
@endphp
<script type="application/json" id="chat-config">@json($configuracaoChat)</script>
<script type="application/json" id="chat-categorias">@json($categoriasChat)</script>
<script type="application/json" id="chat-antigas">@json(old('campos', []))</script>
<script>
    const config = JSON.parse(document.getElementById('chat-config').textContent);
    const categories = JSON.parse(document.getElementById('chat-categorias').textContent);
    const oldAnswers = JSON.parse(document.getElementById('chat-antigas').textContent);
    const transcript = document.getElementById('chat-mensagens');
    const prompt = document.getElementById('chat-pergunta');
    const controls = document.getElementById('chat-resposta');
    const sendButton = document.getElementById('chat-enviar');
    const finalButton = document.getElementById('chat-finalizar');
    const storedInputs = document.getElementById('chat-campos-ocultos');
    const form = document.getElementById('portal-chat-form');
    let position = 0;
    let currentInput;
    const helpDialog = document.getElementById('ajuda-atendimento');
    document.querySelector('[data-abrir-ajuda]')?.addEventListener('click', () => helpDialog.showModal());
    document.querySelector('[data-fechar-ajuda]')?.addEventListener('click', () => helpDialog.close());
    helpDialog?.addEventListener('click', (event) => {
        if (event.target === helpDialog) helpDialog.close();
    });

    const atualizarProgresso = () => {
        const total = config.length;
        const respondidas = Math.min(position, total);
        const percentual = total ? Math.round((respondidas / total) * 100) : 100;
        document.getElementById('chat-progresso').textContent = !total
            ? 'Atendimento pronto'
            : respondidas >= total ? 'Etapas concluídas' : `Pergunta ${respondidas + 1} de ${total}`;
        document.getElementById('chat-progresso-percentual').textContent = `${percentual}%`;
        document.getElementById('chat-progresso-inline').style.width = `${percentual}%`;
        document.querySelector('[role="progressbar"]').setAttribute('aria-valuenow', String(percentual));
    };

    const addMessage = (text, mine = false) => {
        const row = document.createElement('div');
        row.className = 'chat-bolha flex max-w-[94%] items-end gap-2 sm:max-w-[82%] ' + (mine ? 'self-end flex-row-reverse' : 'self-start');
        if (!mine) {
            const avatar = document.createElement('span');
            avatar.className = 'grid h-7 w-7 shrink-0 place-items-center rounded-full bg-[#ff7900] text-white';
            avatar.innerHTML = '<svg viewBox="0 0 32 32" fill="currentColor" class="h-5 w-5" aria-hidden="true"><circle cx="16" cy="5" r="3"/><circle cx="16" cy="13" r="3"/><circle cx="16" cy="21" r="3"/><circle cx="16" cy="29" r="2"/><circle cx="5" cy="16" r="3"/><circle cx="13" cy="16" r="3"/><circle cx="21" cy="16" r="3"/><circle cx="29" cy="16" r="2"/><circle cx="8" cy="8" r="2.4"/><circle cx="24" cy="8" r="2.4"/><circle cx="8" cy="24" r="2.4"/><circle cx="24" cy="24" r="2.4"/></svg>';
            row.append(avatar);
        }
        const bubble = document.createElement('div');
        bubble.className = 'min-w-0 break-words rounded-xl px-4 py-3 text-sm leading-6 shadow-sm sm:px-5 ' + (mine
            ? 'rounded-br-sm border border-[#e86e00] bg-[#ff7900] text-white'
            : 'rounded-bl-sm border border-slate-200 bg-white text-slate-700');
        const content = document.createElement('p');
        content.className = 'whitespace-pre-wrap';
        content.textContent = text;
        const time = document.createElement('time');
        time.className = 'mt-1.5 block text-right text-[10px] font-medium ' + (mine ? 'text-orange-100' : 'text-slate-400');
        time.textContent = new Intl.DateTimeFormat('pt-BR', {hour: '2-digit', minute: '2-digit'}).format(new Date());
        bubble.append(content, time);
        row.append(bubble);
        transcript.append(row);
        transcript.scrollTop = transcript.scrollHeight;
    };
    const addTypingIndicator = () => {
        const row = document.createElement('div');
        row.id = 'chat-digitando';
        row.className = 'chat-bolha flex max-w-[94%] items-end gap-2 self-start sm:max-w-[82%]';
        row.setAttribute('role', 'status');
        row.setAttribute('aria-label', 'O assistente está digitando');
        const avatar = document.createElement('span');
        avatar.className = 'grid h-7 w-7 shrink-0 place-items-center rounded-full bg-[#ff7900] text-white';
        avatar.innerHTML = '<svg viewBox="0 0 32 32" fill="currentColor" class="h-5 w-5" aria-hidden="true"><circle cx="16" cy="5" r="3"/><circle cx="16" cy="13" r="3"/><circle cx="16" cy="21" r="3"/><circle cx="16" cy="29" r="2"/><circle cx="5" cy="16" r="3"/><circle cx="13" cy="16" r="3"/><circle cx="21" cy="16" r="3"/><circle cx="29" cy="16" r="2"/><circle cx="8" cy="8" r="2.4"/><circle cx="24" cy="8" r="2.4"/><circle cx="8" cy="24" r="2.4"/><circle cx="24" cy="24" r="2.4"/></svg>';
        const bubble = document.createElement('div');
        bubble.className = 'flex items-center gap-1.5 rounded-xl rounded-bl-sm border border-slate-200 bg-white px-4 py-4 shadow-sm';
        for (let index = 0; index < 3; index++) {
            const dot = document.createElement('span');
            dot.className = 'typing-dot h-1.5 w-1.5 rounded-full bg-slate-400';
            bubble.append(dot);
        }
        row.append(avatar, bubble);
        transcript.append(row);
        transcript.scrollTop = transcript.scrollHeight;
        return row;
    };
    const askNext = () => {
        if (position >= config.length) {
            prompt.textContent = 'Tudo pronto. Confira suas respostas e abra o chamado.';
            atualizarProgresso();
            addMessage('Obrigado! Revise suas respostas e confirme para registrar o chamado.', false);
            controls.replaceChildren();
            sendButton.classList.add('hidden');
            finalButton.classList.remove('hidden');
            finalButton.classList.add('flex');
            return;
        }

        const field = config[position];
        const name = field.tipo === 'anexo' ? `arquivos[${field.chave}][]` : `campos[${field.chave}]`;
        const placeholder = field.obrigatorio ? 'Digite sua resposta...' : 'Digite sua resposta (opcional)...';
        prompt.textContent = field.rotulo + (field.obrigatorio ? ' · Obrigatório' : ' · Opcional');
        atualizarProgresso();
        controls.replaceChildren();
        const wrapper = document.createElement('div');
        wrapper.className = 'min-w-0 flex-1 px-1';
        if (field.tipo === 'texto_longo') {
            currentInput = document.createElement('textarea');
            currentInput.rows = 1;
            currentInput.placeholder = placeholder;
        } else if (field.tipo === 'selecao' || field.tipo === 'selecao_multipla') {
            currentInput = document.createElement('select');
            if (field.tipo === 'selecao_multipla') {
                currentInput.multiple = true;
                currentInput.size = 3;
            } else {
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = 'Toque para selecionar';
                currentInput.append(placeholder);
            }
            const options = field.chave === 'categoria' ? categories : (field.opcoes || []).map((label) => ({id: label, nome: label}));
            options.forEach((option) => {
                const item = document.createElement('option');
                item.value = option.id;
                item.textContent = option.nome;
                currentInput.append(item);
            });
        } else {
            currentInput = document.createElement('input');
            currentInput.type = field.tipo === 'email' ? 'email'
                : field.tipo === 'numero' ? 'number'
                : field.tipo === 'data' ? 'date'
                : field.tipo === 'anexo' ? 'file'
                : 'text';
            currentInput.placeholder = field.tipo === 'anexo' ? 'Selecionar arquivo(s)' : placeholder;
            if (field.tipo === 'anexo') {
                currentInput.multiple = true;
                const extensions = field.opcoes || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'];
                currentInput.accept = extensions.map((extension) => '.' + extension).join(',');
            }
            if (field.tipo === 'identificacao') {
                currentInput.inputMode = 'numeric';
                currentInput.autocomplete = 'organization';
                currentInput.addEventListener('input', () => {
                    const digits = currentInput.value.replace(/\D/g, '').slice(0, 14);
                    currentInput.value = digits.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2');
                });
            }
        }
        currentInput.name = name;
        currentInput.className = 'chat-texto min-h-11 w-full border-0 bg-transparent px-2 py-2.5 text-sm text-slate-800 outline-none placeholder:text-slate-400 focus:ring-0';
        if (currentInput.tagName === 'TEXTAREA') currentInput.classList.add('max-h-40', 'resize-none', 'overflow-y-auto');
        if (field.tipo !== 'anexo' && field.obrigatorio) currentInput.required = true;
        if (field.tamanho_maximo && ['texto', 'texto_longo', 'identificacao'].includes(field.tipo)) {
            currentInput.maxLength = field.tamanho_maximo;
        }
        const oldValue = oldAnswers[field.chave];
        if (oldValue && field.tipo !== 'anexo') {
            if (field.tipo === 'selecao_multipla') {
                Array.from(currentInput.options).forEach((option) => option.selected = oldValue.includes(option.value));
            } else {
                currentInput.value = oldValue;
            }
        }
        wrapper.append(currentInput);
        controls.append(wrapper);
        if (!field.obrigatorio) {
            const skip = document.createElement('button');
            skip.type = 'button';
            skip.textContent = 'Pular';
            skip.className = 'mb-2 whitespace-nowrap rounded-lg px-2 py-1 text-xs font-semibold text-slate-500 transition hover:bg-orange-50 hover:text-[#e86e00]';
            skip.addEventListener('click', () => acceptAnswer(true));
            controls.append(skip);
        }
        if (currentInput.tagName === 'TEXTAREA') {
            const resize = () => {
                currentInput.style.height = 'auto';
                currentInput.style.height = `${Math.min(currentInput.scrollHeight, 160)}px`;
            };
            currentInput.addEventListener('input', resize);
            resize();
        }
        currentInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && currentInput.tagName === 'TEXTAREA' && !event.shiftKey) {
                event.preventDefault();
                acceptAnswer(false);
                return;
            }
            if (event.key === 'Enter' && currentInput.tagName !== 'TEXTAREA' && !currentInput.multiple) {
                event.preventDefault();
                acceptAnswer(false);
            }
        });
        currentInput.focus();
        if (window.lucide) lucide.createIcons();
    };
    const answerText = (field, input) => {
        if (field.tipo === 'anexo') return Array.from(input.files || []).map((file) => file.name).join(', ');
        if (field.tipo === 'selecao_multipla') return Array.from(input.selectedOptions).map((option) => option.textContent).join(', ');
        if (field.chave === 'categoria') return input.selectedOptions[0]?.textContent || '';
        return input.value.trim();
    };
    const validateCnpj = (value) => {
        const digits = value.replace(/\D/g, '');
        if (!/^\d{14}$/.test(digits) || /^(\d)\1{13}$/.test(digits)) return false;
        const calc = (length) => {
            let weight = length - 7;
            let sum = 0;
            for (let index = length; index >= 1; index--) {
                sum += Number(digits[length - index]) * weight--;
                if (weight < 2) weight = 9;
            }
            const remainder = sum % 11;
            return remainder < 2 ? 0 : 11 - remainder;
        };
        const first = calc(12);
        const second = calc(13);
        return first === Number(digits[12]) && second === Number(digits[13]);
    };
    function acceptAnswer(skip) {
        if (!currentInput || !config[position]) return;
        const field = config[position];
        if (!skip && field.obrigatorio && field.tipo === 'anexo' && !currentInput.files.length) {
            addMessage('Anexe pelo menos um arquivo para continuar.', false);
            return;
        }
        if (!skip && field.obrigatorio && field.tipo !== 'anexo' && !currentInput.reportValidity()) return;
        if (!skip && field.tipo === 'identificacao' && currentInput.value && !validateCnpj(currentInput.value)) {
            currentInput.setCustomValidity('Informe um CNPJ válido.');
            currentInput.reportValidity();
            currentInput.addEventListener('input', () => currentInput.setCustomValidity(''), {once: true});
            return;
        }
        const value = skip ? '' : answerText(field, currentInput);
        if (!skip && !value && !field.obrigatorio && field.tipo !== 'anexo') {
            addMessage('Pulei esta pergunta.', true);
        } else if (!skip) {
            addMessage(value || 'Arquivo anexado', true);
        }
        if (skip) {
            currentInput.remove();
        } else if (field.tipo === 'anexo' && !currentInput.files.length) {
            currentInput.remove();
        } else {
            currentInput.hidden = true;
            currentInput.required = false;
            currentInput.tabIndex = -1;
            if (field.tipo === 'anexo') currentInput.classList.add('hidden');
            storedInputs.append(currentInput);
        }
        position += 1;
        atualizarProgresso();
        sendButton.disabled = true;
        const typing = addTypingIndicator();
        window.setTimeout(() => {
            typing.remove();
            const next = config[position];
            if (next) addMessage(next.descricao ? next.rotulo + '. ' + next.descricao : next.rotulo, false);
            askNext();
            sendButton.disabled = false;
        }, 420);
    }

    sendButton.addEventListener('click', () => acceptAnswer(false));
    form.addEventListener('submit', (event) => {
        if (position < config.length) {
            event.preventDefault();
            return;
        }
        finalButton.disabled = true;
        finalButton.textContent = 'Registrando chamado…';
    });

    addMessage(config[0]?.descricao ? config[0].rotulo + '. ' + config[0].descricao : (config[0]?.rotulo || 'Informe os dados'), false);
    askNext();
    if (window.lucide) lucide.createIcons();
</script>
</body>
</html>
