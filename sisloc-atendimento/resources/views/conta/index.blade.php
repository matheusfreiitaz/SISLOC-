@extends('layouts.app')
@section('titulo', 'Minha conta')
@section('conteudo')
<div class="mx-auto grid max-w-4xl gap-6 md:grid-cols-2">
    <form method="POST" action="{{ route('conta.perfil') }}" enctype="multipart/form-data" class="cartao space-y-4">
        @csrf @method('PUT')
        <h2 class="font-semibold">Perfil</h2>
        <x-campo nome="nome" rotulo="Nome" :valor="$usuario->nome" required />
        <div><label class="rotulo">Foto de perfil</label><input type="file" name="foto" accept="image/*" class="campo">@error('foto')<p class="erro">{{ $message }}</p>@enderror</div>
        <p class="text-xs text-slate-500">Perfil de acesso: {{ $usuario->perfil->nome }} · Último acesso: {{ $usuario->ultimo_acesso_em?->format('d/m/Y H:i') }}</p>
        <button class="btn-primario">Salvar perfil</button>
        <p class="text-xs text-slate-500">O tema (claro/escuro) é alterado pelo botão no cabeçalho e fica salvo na sua conta.</p>
    </form>
    <form method="POST" action="{{ route('conta.senha') }}" class="cartao space-y-4">
        @csrf @method('PUT')
        <h2 class="font-semibold">Alterar senha</h2>
        <x-campo nome="senha_atual" rotulo="Senha atual" tipo="password" required />
        <x-campo nome="password" rotulo="Nova senha (mín. 8, letras e números)" tipo="password" required />
        <x-campo nome="password_confirmation" rotulo="Confirmar nova senha" tipo="password" required />
        <button class="btn-primario">Alterar senha</button>
    </form>
</div>
@endsection
