<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar · SISLOC ATENDIMENTO</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-gradient-to-br from-orange-50 to-slate-100 p-4 font-sans">
<div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
    <div class="mb-6 text-center">
        <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-orange-500 text-xl font-bold text-white">S</div>
        <h1 class="text-xl font-bold text-slate-900">SISLOC <span class="text-orange-500">ATENDIMENTO</span></h1>
        <p class="text-sm text-slate-500">Acesse com suas credenciais</p>
    </div>
    @if (session('sucesso'))<p class="mb-4 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('sucesso') }}</p>@endif
    <form method="POST" action="{{ url('/entrar') }}" class="space-y-4">
        @csrf
        <x-campo nome="email" rotulo="E-mail" tipo="email" required autofocus />
        <x-campo nome="password" rotulo="Senha" tipo="password" required />
        <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="lembrar" value="1"> Manter conectado</label>
        <button class="btn-primario w-full justify-center">Entrar</button>
    </form>
    <p class="mt-4 text-center text-sm"><a class="text-orange-600 hover:underline" href="{{ route('password.request') }}">Esqueci minha senha</a></p>
</div>
</body>
</html>
