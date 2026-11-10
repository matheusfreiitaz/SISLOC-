<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Redefinir senha · SISLOC ATENDIMENTO</title>@vite(['resources/css/app.css'])</head>
<body class="grid min-h-screen place-items-center bg-slate-100 p-4 font-sans">
<div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
    <h1 class="mb-4 text-lg font-bold">Definir nova senha</h1>
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">@csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-campo nome="email" rotulo="E-mail" tipo="email" :valor="$email" required />
        <x-campo nome="password" rotulo="Nova senha (mín. 8, com letras e números)" tipo="password" required />
        <x-campo nome="password_confirmation" rotulo="Confirmar nova senha" tipo="password" required />
        <button class="btn-primario w-full justify-center">Redefinir senha</button>
    </form>
</div></body></html>
