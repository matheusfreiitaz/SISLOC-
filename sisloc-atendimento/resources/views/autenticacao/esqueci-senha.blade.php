<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recuperar senha · SISLOC ATENDIMENTO</title>@vite(['resources/css/app.css'])</head>
<body class="grid min-h-screen place-items-center bg-slate-100 p-4 font-sans">
<div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
    <h1 class="mb-1 text-lg font-bold">Recuperar senha</h1>
    <p class="mb-4 text-sm text-slate-500">Informe o e-mail cadastrado para receber o link de redefinição.</p>
    @if (session('sucesso'))<p class="mb-4 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('sucesso') }}</p>@endif
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">@csrf
        <x-campo nome="email" rotulo="E-mail" tipo="email" required />
        <button class="btn-primario w-full justify-center">Enviar link</button>
    </form>
    <p class="mt-4 text-center text-sm"><a class="text-orange-600 hover:underline" href="{{ route('login') }}">Voltar ao login</a></p>
</div></body></html>
