@extends('layouts.app')
@section('titulo', $usuario->exists ? 'Editar usuário' : 'Novo usuário')
@section('conteudo')
<form method="POST" action="{{ $usuario->exists ? route('admin.usuarios.update', $usuario) : route('admin.usuarios.store') }}" class="cartao mx-auto max-w-2xl space-y-4">
    @csrf @if ($usuario->exists) @method('PUT') @endif
    <x-campo nome="nome" rotulo="Nome" :valor="$usuario->nome" required />
    <x-campo nome="email" rotulo="E-mail" tipo="email" :valor="$usuario->email" required />
    <x-campo nome="password" :rotulo="$usuario->exists ? 'Nova senha (deixe em branco para manter)' : 'Senha inicial (mín. 8, letras e números)'" tipo="password" />
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="rotulo">Perfil</label><select name="perfil_id" class="campo" required>
            @foreach ($perfis as $p)<option value="{{ $p->id }}" @selected(old('perfil_id', $usuario->perfil_id) == $p->id)>{{ $p->nome }}</option>@endforeach</select></div>
        <div><label class="rotulo">Departamento</label><select name="departamento_id" class="campo"><option value="">—</option>
            @foreach ($departamentos as $d)<option value="{{ $d->id }}" @selected(old('departamento_id', $usuario->departamento_id) == $d->id)>{{ $d->nome }}</option>@endforeach</select></div>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="ativo" value="1" @checked(old('ativo', $usuario->ativo))> Conta ativa</label>
    <div class="flex justify-end gap-2"><a href="{{ route('admin.usuarios.index') }}" class="btn-neutro">Cancelar</a><button class="btn-primario">Salvar</button></div>
</form>
@endsection
