@extends('layouts.app')
@section('titulo', 'Usuários')
@section('conteudo')
<div class="cartao">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex gap-2"><input class="campo" name="busca" value="{{ request('busca') }}" placeholder="Buscar nome ou e-mail"><button class="btn-neutro">Buscar</button></form>
        <a href="{{ route('admin.usuarios.create') }}" class="btn-primario"><i data-lucide="user-plus" class="h-4 w-4"></i> Novo usuário</a>
    </div>
    <div class="overflow-x-auto"><table class="tabela">
        <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Departamento</th><th>Último acesso</th><th>Situação</th><th></th></tr></thead>
        <tbody>@foreach ($usuarios as $u)
            <tr><td>{{ $u->nome }}</td><td>{{ $u->email }}</td><td>{{ $u->perfil->nome }}</td><td>{{ $u->departamento?->nome ?? '—' }}</td>
                <td>{{ $u->ultimo_acesso_em?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
                <td><x-selo-status :cor="$u->ativo ? '#22c55e' : '#ef4444'" :texto="$u->ativo ? 'Ativo' : 'Inativo'" /></td>
                <td><a class="text-orange-600 hover:underline" href="{{ route('admin.usuarios.edit', $u) }}">Editar</a></td></tr>
        @endforeach</tbody></table></div>
    <div class="mt-4">{{ $usuarios->links() }}</div>
</div>
@endsection
