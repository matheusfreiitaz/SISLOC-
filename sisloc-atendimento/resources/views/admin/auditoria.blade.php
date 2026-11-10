@extends('layouts.app')
@section('titulo', 'Auditoria')
@section('conteudo')
<section class="cartao mb-6">
    <div class="mb-4">
        <h2 class="font-semibold">Notificações recebidas por colaborador</h2>
        <p class="mt-1 text-sm text-slate-500">Inclui alertas de monitoramento do atendimento, além de notificações lidas e pendentes.</p>
    </div>
    <div class="overflow-x-auto"><table class="tabela">
        <thead><tr><th>Colaborador</th><th>Perfil</th><th>Notificações recebidas</th><th>Alertas de atenção</th><th>Pendentes</th></tr></thead>
        <tbody>
            @foreach ($colaboradores as $colaborador)
                @php($resumo = $resumoNotificacoes->get($colaborador->id))
                <tr>
                    <td>{{ $colaborador->nome }}</td>
                    <td>{{ $colaborador->perfil?->nome ?? '—' }}</td>
                    <td>{{ $resumo->total ?? 0 }}</td>
                    <td>{{ $resumo->alertas_atencao ?? 0 }}</td>
                    <td>{{ $resumo->pendentes ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table></div>
</section>
<div class="cartao">
    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <input class="campo max-w-xs" name="acao" value="{{ request('acao') }}" placeholder="Filtrar ação (ex.: chamado.)">
        <select class="campo max-w-xs" name="usuario_id">
            <option value="">Todos os colaboradores</option>
            @foreach ($colaboradores as $colaborador)
                <option value="{{ $colaborador->id }}" @selected((string) request('usuario_id') === (string) $colaborador->id)>{{ $colaborador->nome }}</option>
            @endforeach
        </select>
        <button class="btn-neutro">Filtrar logs</button>
    </form>
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
