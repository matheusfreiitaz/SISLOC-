@extends('layouts.app')
@section('titulo', 'Fila de atendimento')
@section('conteudo')
<div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div><h2 class="text-xl font-bold">Fila de atendimento</h2><p class="text-sm text-slate-500">Chamados ativos visíveis conforme suas permissões. Atualize a página para sincronizar.</p></div>
        <span class="rounded-full bg-orange-100 px-3 py-1 text-sm font-semibold text-orange-800">{{ $chamados->total() }} na fila</span>
    </div>
    <form method="GET" class="cartao grid gap-3 sm:grid-cols-2 lg:grid-cols-7">
        <input class="campo" name="busca" value="{{ request('busca') }}" placeholder="Protocolo, CNPJ, solicitante">
        <select class="campo" name="departamento_id"><option value="">Todos os departamentos</option>@foreach ($departamentos as $departamento)<option value="{{ $departamento->id }}" @selected((string) request('departamento_id') === (string) $departamento->id)>{{ $departamento->nome }}</option>@endforeach</select>
        <select class="campo" name="prioridade_id"><option value="">Todas as prioridades</option>@foreach ($prioridades as $prioridade)<option value="{{ $prioridade->id }}" @selected((string) request('prioridade_id') === (string) $prioridade->id)>{{ $prioridade->nome }}</option>@endforeach</select>
        <select class="campo" name="status_id"><option value="">Todos os status ativos</option>@foreach ($statusLista as $status)<option value="{{ $status->id }}" @selected((string) request('status_id') === (string) $status->id)>{{ $status->nome }}</option>@endforeach</select>
        <select class="campo" name="ordem">
            <option value="antigos" @selected($ordem === 'antigos')>Mais antigos</option>
            <option value="recentes" @selected($ordem === 'recentes')>Mais recentes</option>
            <option value="prioridade" @selected($ordem === 'prioridade')>Maior prioridade</option>
            <option value="espera" @selected($ordem === 'espera')>Maior tempo de espera</option>
        </select>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="sem_responsavel" value="1" @checked(request()->boolean('sem_responsavel'))> Sem responsável</label>
        <button class="btn-primario justify-center">Filtrar</button>
    </form>
    <div class="cartao overflow-x-auto">
        <table class="tabela min-w-[1050px]">
            <thead><tr><th>Chamado</th><th>Solicitante</th><th>Empresa</th><th>CNPJ</th><th>Categoria</th><th>Abertura</th><th>Prioridade</th><th>Espera</th><th>Status</th><th>Departamento</th><th>Agente</th></tr></thead>
            <tbody>
            @forelse ($chamados as $chamado)
                <tr>
                    <td class="font-mono text-xs"><a class="text-orange-600 hover:underline" href="{{ route('chamados.show', $chamado) }}">{{ $chamado->numero }}</a></td>
                    <td>{{ $chamado->nomeSolicitante() }}</td>
                    <td>{{ $chamado->empresa_nome ?: '—' }}</td><td>{{ $chamado->cnpjFormatado() ?: '—' }}</td>
                    <td>{{ $chamado->categoria?->nome ?? '—' }}</td>
                    <td>{{ $chamado->created_at->format('d/m/Y H:i') }}</td>
                    <td><x-selo-status :cor="$chamado->prioridade->cor" :texto="$chamado->prioridade->nome" /></td>
                    <td>{{ $chamado->created_at->diffForHumans() }}</td>
                    <td><x-selo-status :cor="$chamado->status->cor" :texto="$chamado->status->nome" /></td>
                    <td>{{ $chamado->departamento->nome }}</td>
                    <td>{{ $chamado->agente?->nome ?? 'Sem responsável' }}</td>
                </tr>
            @empty<tr><td colspan="11" class="py-6 text-center text-slate-500">Nenhum atendimento aguardando na fila.</td></tr>@endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $chamados->links() }}</div>
    </div>
</div>
<script>window.setInterval(() => window.location.reload(), 30000);</script>
@endsection
