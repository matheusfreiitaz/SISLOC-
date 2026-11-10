@extends('layouts.app')
@section('titulo', 'Perfis e permissões')
@section('conteudo')
<div class="cartao">
    <div class="mb-4 flex justify-end"><a href="{{ route('admin.perfis.create') }}" class="btn-primario"><i data-lucide="plus" class="h-4 w-4"></i> Novo perfil</a></div>
    <table class="tabela"><thead><tr><th>Perfil</th><th>Descrição</th><th>Usuários</th><th>Permissões</th><th></th></tr></thead><tbody>
    @foreach ($perfis as $p)
        <tr><td class="font-medium">{{ $p->nome }} @if ($p->sistema)<span class="text-xs text-slate-400">(sistema)</span>@endif</td><td>{{ $p->descricao }}</td>
            <td>{{ $p->usuarios_count }}</td><td>{{ $p->slug === 'administrador' ? 'Todas' : $p->permissoes_count }}</td>
            <td class="flex gap-3"><a class="text-orange-600 hover:underline" href="{{ route('admin.perfis.edit', $p) }}">Editar</a>
                @unless ($p->sistema)
                <form method="POST" action="{{ route('admin.perfis.destroy', $p) }}" onsubmit="return confirm('Excluir este perfil?')">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Excluir</button></form>
                @endunless</td></tr>
    @endforeach</tbody></table>
</div>
@endsection
