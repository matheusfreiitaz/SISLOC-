@extends('layouts.app')
@section('titulo', 'Auditoria')
@section('conteudo')
<div class="cartao">
    <form method="GET" class="mb-4 flex gap-2"><input class="campo max-w-xs" name="acao" value="{{ request('acao') }}" placeholder="Filtrar ação (ex.: chamado.)"><button class="btn-neutro">Filtrar</button></form>
    <div class="overflow-x-auto"><table class="tabela">
        <thead><tr><th>Data</th><th>Usuário</th><th>Ação</th><th>Entidade</th><th>IP</th><th>Detalhes</th></tr></thead>
        <tbody>@foreach ($registros as $r)
            <tr><td class="whitespace-nowrap">{{ $r->created_at->format('d/m/Y H:i:s') }}</td><td>{{ $r->usuario?->nome ?? 'sistema' }}</td>
                <td class="font-mono text-xs">{{ $r->acao }}</td><td>{{ $r->entidade }} {{ $r->entidade_id ? '#'.$r->entidade_id : '' }}</td><td>{{ $r->ip }}</td>
                <td class="max-w-xs truncate font-mono text-xs">{{ $r->detalhes ? json_encode($r->detalhes, JSON_UNESCAPED_UNICODE) : '' }}</td></tr>
        @endforeach</tbody></table></div>
    <div class="mt-4">{{ $registros->links() }}</div>
</div>
@endsection
