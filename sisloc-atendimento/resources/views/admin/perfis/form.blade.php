@extends('layouts.app')
@section('titulo', $perfil->exists ? 'Editar perfil' : 'Novo perfil')
@section('conteudo')
@php $marcadas = old('permissoes', $perfil->exists ? $perfil->permissoes->pluck('id')->all() : []); $ehAdmin = $perfil->slug === 'administrador'; @endphp
<form method="POST" action="{{ $perfil->exists ? route('admin.perfis.update', $perfil) : route('admin.perfis.store') }}" class="cartao mx-auto max-w-3xl space-y-4">
    @csrf @if ($perfil->exists) @method('PUT') @endif
    <x-campo nome="nome" rotulo="Nome do perfil" :valor="$perfil->nome" required />
    <x-campo nome="descricao" rotulo="Descrição" :valor="$perfil->descricao" />
    @if ($ehAdmin)<p class="rounded-xl bg-orange-50 p-3 text-sm text-orange-800 dark:bg-orange-500/10 dark:text-orange-300">O perfil Administrador possui acesso total e não pode ter permissões removidas.</p>@endif
    @foreach ($permissoes as $modulo => $lista)
        <fieldset class="rounded-xl border border-slate-200 p-3 dark:border-slate-800">
            <legend class="px-1 text-sm font-semibold text-orange-600">{{ $modulo }}</legend>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($lista as $perm)
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="permissoes[]" value="{{ $perm->id }}" @checked($ehAdmin || in_array($perm->id, $marcadas)) @disabled($ehAdmin)> {{ $perm->descricao }}</label>
                @endforeach
            </div>
        </fieldset>
    @endforeach
    <div class="flex justify-end gap-2"><a href="{{ route('admin.perfis.index') }}" class="btn-neutro">Cancelar</a><button class="btn-primario">Salvar</button></div>
</form>
@endsection
