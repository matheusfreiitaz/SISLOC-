<!DOCTYPE html>
<html lang="pt-BR" class="{{ auth()->user()?->tema === 'escuro' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Painel') · SISLOC ATENDIMENTO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js"></script>
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
<div class="flex min-h-screen" x-data>
    <aside id="menu" class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-slate-200 bg-white p-4 lg:flex dark:border-slate-800 dark:bg-slate-900">
        <a href="{{ route('painel') }}" class="mb-6 flex items-center gap-2 px-2">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-orange-500 text-white"><i data-lucide="headset" class="h-5 w-5"></i></span>
            <span class="text-sm font-bold leading-tight">SISLOC<br><span class="text-orange-500">ATENDIMENTO</span></span>
        </a>
        <nav class="flex flex-1 flex-col gap-1 overflow-y-auto">
            <a class="menu-item" href="{{ route('painel') }}"><i data-lucide="layout-dashboard" class="h-4 w-4"></i> Início</a>
            @can('chamados.ver')
                <a class="menu-item" href="{{ route('chamados.index') }}"><i data-lucide="ticket" class="h-4 w-4"></i> Chamados</a>
            @endcan
            @can('chamados.criar')
                <a class="menu-item" href="{{ route('chamados.create') }}"><i data-lucide="plus-circle" class="h-4 w-4"></i> Novo chamado</a>
            @endcan
            @can('fila.ver')<a class="menu-item" href="{{ route('fila.index') }}"><i data-lucide="list-ordered" class="h-4 w-4"></i> Fila de Atendimento</a>@endcan
            @can('fila.ver')<a class="menu-item" href="{{ route('pausas.index') }}"><i data-lucide="coffee" class="h-4 w-4"></i> Pausas da equipe</a>@endcan
            @can('finalizados.ver')<a class="menu-item" href="{{ route('finalizados.index') }}"><i data-lucide="archive" class="h-4 w-4"></i> Atendimentos Finalizados</a>@endcan
            @canany(['usuarios.gerenciar', 'perfis.gerenciar', 'cadastros.gerenciar', 'finalizacoes.gerenciar', 'auditoria.ver', 'formulario.configurar'])
                <p class="mt-5 px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Administração</p>
            @endcanany
            @can('cadastros.gerenciar')<a class="menu-item" href="{{ route('admin.monitoramento.index') }}"><i data-lucide="activity" class="h-4 w-4"></i> Monitoramento</a>@endcan
            @can('usuarios.gerenciar')<a class="menu-item" href="{{ route('admin.usuarios.index') }}"><i data-lucide="users" class="h-4 w-4"></i> Usuários</a>@endcan
            @can('perfis.gerenciar')<a class="menu-item" href="{{ route('admin.perfis.index') }}"><i data-lucide="shield-check" class="h-4 w-4"></i> Perfis e permissões</a>@endcan
            @can('cadastros.gerenciar')
                <a class="menu-item" href="{{ route('admin.cadastros.index', 'departamentos') }}"><i data-lucide="building-2" class="h-4 w-4"></i> Departamentos</a>
                <a class="menu-item" href="{{ route('admin.cadastros.index', 'categorias') }}"><i data-lucide="folder-tree" class="h-4 w-4"></i> Categorias</a>
                <a class="menu-item" href="{{ route('admin.cadastros.index', 'prioridades') }}"><i data-lucide="flag" class="h-4 w-4"></i> Prioridades</a>
                <a class="menu-item" href="{{ route('admin.cadastros.index', 'status') }}"><i data-lucide="list-checks" class="h-4 w-4"></i> Status</a>
            @endcan
            @can('finalizacoes.gerenciar')<a class="menu-item" href="{{ route('admin.cadastros.index', 'finalizacoes') }}"><i data-lucide="check-circle-2" class="h-4 w-4"></i> Finalizações</a>@endcan
            @can('formulario.configurar')<a class="menu-item" href="{{ route('admin.formulario-publico.index') }}"><i data-lucide="list-plus" class="h-4 w-4"></i> Configuração do formulário</a>@endcan
            @can('auditoria.ver')<a class="menu-item" href="{{ route('admin.auditoria') }}"><i data-lucide="scroll-text" class="h-4 w-4"></i> Auditoria</a>@endcan
            <a class="menu-item mt-4" href="{{ route('portal.chamados.criar') }}"><i data-lucide="external-link" class="h-4 w-4"></i> Portal do cliente</a>
        </nav>
    </aside>

    <div class="flex min-h-screen flex-1 flex-col lg:pl-64">
        <header class="sticky top-0 z-20 flex items-center justify-between gap-3 border-b border-slate-200 bg-white/90 px-4 py-3 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
            <button class="btn-neutro lg:hidden" type="button" onclick="document.getElementById('menu').classList.toggle('hidden'); document.getElementById('menu').classList.toggle('flex')" aria-label="Abrir menu">
                <i data-lucide="menu" class="h-4 w-4"></i>
            </button>
            <h1 class="text-lg font-bold">@yield('titulo', 'Painel')</h1>
            <div class="flex items-center gap-2">
                <button type="button" onclick="alternarTema()" class="btn-neutro" aria-label="Alternar tema"><i data-lucide="sun-moon" class="h-4 w-4"></i></button>
                <a href="{{ route('notificacoes.index') }}" class="btn-neutro relative" aria-label="Notificações">
                    <i data-lucide="bell" class="h-4 w-4"></i>
                    @if ($totalNotificacoes > 0)
                        <span class="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-orange-500 px-1 text-[10px] font-bold text-white">{{ $totalNotificacoes }}</span>
                    @endif
                </a>
                <a href="{{ route('conta') }}" class="btn-neutro">
                    @if (auth()->user()->urlFoto())
                        <img src="{{ auth()->user()->urlFoto() }}" alt="" class="h-5 w-5 rounded-full object-cover">
                    @else
                        <i data-lucide="user-circle" class="h-4 w-4"></i>
                    @endif
                    <span class="hidden sm:inline">{{ auth()->user()->nome }}</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="btn-neutro" aria-label="Sair"><i data-lucide="log-out" class="h-4 w-4"></i></button>
                </form>
            </div>
        </header>

        <main class="flex-1 p-4 lg:p-8">
            @if (auth()->user()->trocar_senha)
                <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
                    Sua conta usa uma senha provisória. <a class="font-semibold underline" href="{{ route('conta') }}">Altere a senha agora.</a>
                </div>
            @endif
            @if (session('sucesso'))
                <div class="mb-4 rounded-xl border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ session('sucesso') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-red-300 bg-red-50 p-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                    <ul class="list-disc pl-5">@foreach ($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('conteudo')
        </main>
    </div>
</div>
<script>lucide.createIcons();</script>
</body>
</html>
