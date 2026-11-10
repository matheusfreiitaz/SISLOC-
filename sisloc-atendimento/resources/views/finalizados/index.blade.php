@extends('layouts.app')
@section('titulo', 'Atendimentos finalizados')
@section('conteudo')
<div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div><h2 class="text-xl font-bold">Atendimentos finalizados</h2><p class="text-sm text-slate-500">Histórico preservado dos chamados dentro do seu escopo de acesso.</p></div>
        @can('finalizados.exportar')
            <a class="btn-neutro" href="{{ route('finalizados.exportar', request()->query()) }}"><i data-lucide="download" class="h-4 w-4"></i> Exportar CSV</a>
        @endcan
    </div>
    <form method="GET" class="cartao grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <input class="campo" name="busca" value="{{ request('busca') }}" placeholder="Protocolo, CNPJ, empresa, agente">
        <select class="campo" name="departamento_id"><option value="">Todos os departamentos</option>@foreach ($departamentos as $d)<option value="{{ $d->id }}" @selected((string) request('departamento_id') === (string) $d->id)>{{ $d->nome }}</option>@endforeach</select>
        <select class="campo" name="categoria_id"><option value="">Todas as categorias</option>@foreach ($categorias as $c)<option value="{{ $c->id }}" @selected((string) request('categoria_id') === (string) $c->id)>{{ $c->nome }}</option>@endforeach</select>
        <select class="campo" name="motivo_id"><option value="">Todos os motivos</option>@foreach ($motivos as $m)<option value="{{ $m->id }}" @selected((string) request('motivo_id') === (string) $m->id)>{{ $m->nome }}</option>@endforeach</select>
        <select class="campo" name="agente_id"><option value="">Todos os agentes</option>@foreach ($agentes as $agente)<option value="{{ $agente->id }}" @selected((string) request('agente_id') === (string) $agente->id)>{{ $agente->nome }}</option>@endforeach</select>
        <label class="text-sm text-slate-500">Finalizado a partir de<input class="campo mt-1" type="date" name="de" value="{{ request('de') }}"></label>
        <label class="text-sm text-slate-500">Finalizado até<input class="campo mt-1" type="date" name="ate" value="{{ request('ate') }}"></label>
        <div class="flex items-end gap-2"><button class="btn-primario flex-1 justify-center">Filtrar</button><a class="btn-neutro" href="{{ route('finalizados.index') }}">Limpar</a></div>
    </form>
    <div class="cartao overflow-x-auto">
        <table class="tabela min-w-[1100px]">
            <thead><tr><th>Protocolo</th><th>Solicitante</th><th>Empresa</th><th>CNPJ</th><th>Categoria</th><th>Agente</th><th>Departamento</th><th>Abertura</th><th>Finalização</th><th>Duração</th><th>Motivo</th><th>Solução</th></tr></thead>
            <tbody>
            @forelse ($chamados as $chamado)
                <tr>
                    <td class="font-mono text-xs"><a class="text-orange-600 hover:underline" href="{{ route('chamados.show', $chamado) }}">{{ $chamado->numero }}</a></td>
                    <td>{{ $chamado->nomeSolicitante() }}</td><td>{{ $chamado->empresa_nome ?: '—' }}</td><td>{{ $chamado->cnpjFormatado() ?: '—' }}</td>
                    <td>{{ $chamado->categoria?->nome ?? '—' }}</td><td>{{ $chamado->agente?->nome ?? '—' }}</td>
                    <td>{{ $chamado->departamento->nome }}</td><td>{{ $chamado->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $chamado->finalizado_em?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>{{ $chamado->finalizado_em ? $chamado->created_at->diffForHumans($chamado->finalizado_em, true) : '—' }}</td>
                    <td>{{ $chamado->finalizacao?->motivo?->nome ?? '—' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($chamado->finalizacao?->descricao_solucao ?? '—', 50) }}</td>
                </tr>
            @empty<tr><td colspan="12" class="py-6 text-center text-slate-500">Nenhum atendimento finalizado encontrado.</td></tr>@endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $chamados->links() }}</div>
    </div>
</div>
@endsection
