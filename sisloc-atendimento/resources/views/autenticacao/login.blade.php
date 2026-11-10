<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acesso · SISLOC ATENDIMENTO</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .acesso-fundo {
            background:
                radial-gradient(ellipse at 8% 8%, rgba(249, 115, 22, .13), transparent 34rem),
                radial-gradient(ellipse at 96% 90%, rgba(124, 58, 237, .08), transparent 32rem),
                #f8fafc;
        }
        .acesso-cartao {
            box-shadow: 0 24px 70px -35px rgba(15, 23, 42, .25);
        }
    </style>
</head>
<body class="acesso-fundo min-h-screen px-4 py-8 font-sans text-slate-900 sm:py-12">
<main class="mx-auto max-w-5xl">
    <header class="mb-9 flex flex-wrap items-center justify-between gap-4">
        <a href="{{ route('login') }}" class="flex items-center gap-3" aria-label="SISLOC Atendimento, início">
            <span class="grid h-11 w-11 place-items-center rounded-2xl bg-orange-500 text-white shadow-lg shadow-orange-500/20">
                <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" aria-hidden="true">
                    <path d="M4.5 13.5v-2a7.5 7.5 0 0 1 15 0v2M4.5 13.5H7a2 2 0 0 1 2 2v3H6a3 3 0 0 1-3-3v-1a1 1 0 0 1 1-1Zm15 0H17a2 2 0 0 0-2 2v3h3a3 3 0 0 0 3-3v-1a1 1 0 0 0-1-1Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <span class="text-sm font-extrabold leading-tight tracking-wide">SISLOC<br><span class="text-orange-500">ATENDIMENTO</span></span>
        </a>
        <span class="rounded-full border border-slate-200 bg-white/80 px-3 py-1.5 text-xs font-medium text-slate-500">
            Central de atendimento
        </span>
    </header>

    <section class="mb-8 max-w-2xl">
        <p class="mb-3 inline-flex items-center gap-2 rounded-full bg-orange-100 px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-orange-700">
            <span class="h-1.5 w-1.5 rounded-full bg-orange-500"></span>
            Estamos aqui para ajudar
        </p>
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Como você quer acessar?</h1>
        <p class="mt-3 max-w-xl text-sm leading-6 text-slate-600 sm:text-base">
            Escolha sua área para receber atendimento ou acessar as ferramentas da equipe SISLOC.
        </p>
    </section>

    @if (session('sucesso'))
        <p class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('sucesso') }}</p>
    @endif
    @if ($errors->has('email') || $errors->has('password'))
        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
            {{ $errors->first('email') ?: $errors->first('password') }}
        </div>
    @endif

    <div class="grid items-stretch gap-5 lg:grid-cols-2">
        <section class="acesso-cartao flex flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white">
            <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 via-orange-500 to-amber-500 p-6 text-white sm:p-8">
                <div class="absolute -right-10 -top-14 h-44 w-44 rounded-full border-[24px] border-white/10"></div>
                <span class="relative grid h-12 w-12 place-items-center rounded-2xl bg-white/15 ring-1 ring-white/25">
                    <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" aria-hidden="true">
                        <path d="M4 5.75A2.75 2.75 0 0 1 6.75 3h10.5A2.75 2.75 0 0 1 20 5.75v7.5A2.75 2.75 0 0 1 17.25 16H11l-4.5 4v-4.16A2.75 2.75 0 0 1 4 13.25v-7.5Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 8h8M8 11.5h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </span>
                <p class="relative mt-5 text-xs font-bold uppercase tracking-[.18em] text-orange-50">Área do cliente</p>
                <h2 class="relative mt-1 text-2xl font-bold">Sou cliente</h2>
                <p class="relative mt-2 max-w-sm text-sm leading-6 text-orange-50">
                    Converse com nossa equipe e acompanhe seu atendimento em um só lugar.
                </p>
            </div>
            <div class="flex flex-1 flex-col p-6 sm:p-8">
                <ul class="mb-7 space-y-4 text-sm text-slate-600">
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700">✓</span>
                        Abra um chamado pelo chat guiado
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700">✓</span>
                        Envie mensagens e arquivos para a equipe
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700">✓</span>
                        Consulte o histórico pelo link individual do atendimento
                    </li>
                </ul>
                <a href="{{ route('portal.chamados.criar') }}" class="mt-auto inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-orange-500 px-5 py-3 text-sm font-bold text-white transition hover:bg-orange-600 focus:outline-none focus:ring-4 focus:ring-orange-500/25">
                    Iniciar conversa
                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
                <p class="mt-4 text-center text-xs leading-5 text-slate-500">
                    Já abriu um chamado? Use o link individual enviado para seu e-mail para voltar ao chat e ao histórico.
                </p>
            </div>
        </section>

        <section class="acesso-cartao flex flex-col rounded-3xl border border-slate-200 bg-white p-6 sm:p-8">
            <div class="flex items-start gap-4">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-slate-900 text-white">
                    <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" aria-hidden="true">
                        <path d="M16 20v-1.5a4.5 4.5 0 0 0-4.5-4.5h-3A4.5 4.5 0 0 0 4 18.5V20m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8-3h4m-2-2v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.18em] text-slate-400">Área interna</p>
                    <h2 class="mt-1 text-2xl font-bold text-slate-900">Sou funcionário</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Entre para acessar o ambiente de trabalho conforme as permissões do seu perfil.</p>
                </div>
            </div>

            <div class="my-6 h-px bg-slate-100"></div>
            <form method="POST" action="/entrar" class="flex flex-1 flex-col gap-4">
                @csrf
                <x-campo nome="email" rotulo="E-mail corporativo" tipo="email" required autofocus autocomplete="username" placeholder="voce@sisloc.com.br" />
                <x-campo nome="password" rotulo="Senha" tipo="password" required autocomplete="current-password" />
                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="lembrar" value="1" class="rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                    Manter conectado
                </label>
                <button class="mt-1 inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-900/15">
                    Entrar no ambiente de trabalho
                    <svg viewBox="0 0 20 20" fill="none" class="h-4 w-4" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </form>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-4 text-sm">
                <span class="text-slate-500">Problemas para acessar?</span>
                <a class="font-semibold text-orange-600 hover:text-orange-700 hover:underline" href="{{ route('password.request') }}">Recuperar senha</a>
            </div>
            <div class="mt-5 rounded-2xl bg-slate-50 p-4">
                <p class="text-xs font-semibold text-slate-700">Seu espaço de trabalho</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">Painel, fila de atendimento, chamados, conversas, notificações e recursos administrativos aparecem conforme seu perfil de acesso.</p>
            </div>
        </section>
    </div>

    <footer class="mt-8 flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-center text-xs text-slate-400">
        <span>Atendimento seguro</span><span aria-hidden="true">·</span><span>Clientes e equipe em espaços separados</span>
    </footer>
</main>
</body>
</html>
