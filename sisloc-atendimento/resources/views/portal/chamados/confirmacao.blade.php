<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chamado registrado · SISLOC ATENDIMENTO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js"></script>
</head>
<body class="grid min-h-screen place-items-center bg-[radial-gradient(ellipse_at_10%_10%,rgba(255,121,0,.1),transparent_30rem),#f5f6f8] p-4 font-sans text-slate-900">
<main class="w-full max-w-2xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10">
    <div class="relative overflow-hidden bg-gradient-to-br from-[#202124] to-[#3a291d] px-6 py-8 text-center text-white sm:px-10 sm:py-10">
        <div class="pointer-events-none absolute -right-10 -top-20 h-56 w-56 rounded-full bg-orange-500/20 blur-3xl"></div>
        <span class="relative mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-emerald-400/15 text-emerald-300 ring-1 ring-emerald-300/30"><i data-lucide="check-check" class="h-8 w-8"></i></span>
        <p class="relative mt-5 text-xs font-bold uppercase tracking-[.18em] text-orange-200">Central de atendimento SISLOC</p>
        <h1 class="relative mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Solicitação registrada!</h1>
        <p class="relative mt-2 text-sm text-slate-300">Sua solicitação já foi encaminhada para nossa equipe.</p>
    </div>
    <div class="px-6 py-7 text-center sm:px-10 sm:py-8">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Seu número de protocolo</p>
        <p class="mt-2 font-mono text-3xl font-bold tracking-wide text-orange-600">#{{ session('protocolo') }}</p>
        <div class="mx-auto mt-6 max-w-lg rounded-2xl border border-orange-100 bg-orange-50/70 p-4 text-left">
            <div class="flex items-start gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-white text-orange-600 shadow-sm"><i data-lucide="mail-check" class="h-4 w-4"></i></span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Acompanhe pelo seu e-mail</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-600">Enviamos um link individual para continuar a conversa com a equipe e consultar as atualizações do chamado.</p>
                </div>
            </div>
        </div>
        @if (session('acompanhamento'))
            <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ session('acompanhamento') }}" class="btn-primario justify-center"><i data-lucide="messages-square" class="h-4 w-4"></i> Acompanhar meu chamado</a>
                <button type="button" class="btn-neutro justify-center" data-copiar-link data-link="{{ session('acompanhamento') }}"><i data-lucide="copy" class="h-4 w-4"></i><span data-copiar-texto>Copiar link seguro</span></button>
            </div>
            <p class="mt-3 text-xs text-slate-500" data-copiar-status role="status" aria-live="polite"></p>
        @else
            <p class="mt-5 text-sm text-slate-600">Verifique sua caixa de entrada para acessar o link seguro do chamado.</p>
        @endif
        <div class="mt-7 border-t border-slate-100 pt-5">
            <a href="{{ route('portal.chamados.criar') }}" class="text-sm font-semibold text-slate-600 transition hover:text-orange-700">Abrir outro chamado</a>
        </div>
    </div>
</main>
<script>
    document.querySelector('[data-copiar-link]')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        const status = document.querySelector('[data-copiar-status]');
        try {
            await navigator.clipboard.writeText(button.dataset.link);
            status.textContent = 'Link copiado. Guarde-o para consultar seu atendimento.';
        } catch {
            status.textContent = 'Não foi possível copiar automaticamente. Use o botão “Acompanhar meu chamado”.';
        }
    });
</script>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
