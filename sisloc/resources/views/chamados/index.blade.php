@extends('layouts.app')
@section('titulo', 'Chamados')
@section('conteudo')
<form method="GET" class="cartao mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    <div class="lg:col-span-2"><label class="rotulo">Buscar (número, assunto, usuário ou agente)</label><input class="campo" name="busca" value="{{ request('busca') }}"></div>
    <div><label class="rotulo">Status</label><select class="campo" name="status_id"><option value="">Todos</option>
        @foreach ($statusLista as $s)<option value="{{ $s->id }}" @selected(request('status_id') == $s->id)>{{ $s->nome }}</option>@endforeach</select></div>
    <div><label class="rotulo">Prioridade</label><select class="campo" name="prioridade_id"><option value="">Todas</option>
        @foreach ($prioridades as $p)<option value="{{ $p->id }}" @selected(request('prioridade_id') == $p->id)>{{ $p->nome }}</option>@endforeach</select></div>
    <div><label class="rotulo">Departamento</label><select class="campo" name="departamento_id"><option value="">Todos</option>
        @foreach ($departamentos as $d)<option value="{{ $d->id }}" @selected(request('departamento_id') == $d->id)>{{ $d->nome }}</option>@endforeach</select></div>
    <div><label class="rotulo">Agente</label><select class="campo" name="agente_id"><option value="">Todos</option>
        @foreach ($agentes as $a)<option value="{{ $a->id }}" @selected(request('agente_id') == $a->id)>{{ $a->nome }}</option>@endforeach</select></div>
    <div><label class="rotulo">De</label><input type="date" class="campo" name="de" value="{{ request('de') }}"></div>
    <div><label class="rotulo">Até</label><input type="date" class="campo" name="ate" value="{{ request('ate') }}"></div>
    <div class="flex items-end gap-4 text-sm lg:col-span-3">
        <label class="flex items-center gap-2"><input type="checkbox" name="atrasados" value="1" @checked(request('atrasados'))> Atrasados</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="sem_responsavel" value="1" @checked(request('sem_responsavel'))> Sem responsável</label>
    </div>
    <div class="flex items-end justify-end gap-2">
        <a href="{{ route('chamados.index') }}" class="btn-neutro">Limpar</a>
        <button class="btn-primario"><i data-lucide="search" class="h-4 w-4"></i> Filtrar</button>
    </div>
</form>
<div class="cartao">
    @include('chamados._tabela', ['chamados' => $chamados])
    <div class="mt-4">{{ $chamados->links() }}</div>
</div>
@endsection
