@extends('layouts.app')
@section('titulo', 'Início')
@section('conteudo')
@php
    $cartoes = [
        ['Total de chamados', $indicadores['total'], 'ticket', route('chamados.index')],
        ['Abertos', $indicadores['abertos'], 'inbox', route('chamados.index', ['status_id' => \App\Models\StatusChamado::idPorSlug('aberto')])],
        ['Em atendimento', $indicadores['em_atendimento'], 'loader', route('chamados.index', ['status_id' => \App\Models\StatusChamado::idPorSlug('em_atendimento')])],
        ['Aguardando usuário', $indicadores['aguardando_usuario'], 'hourglass', route('chamados.index', ['status_id' => \App\Models\StatusChamado::idPorSlug('aguardando_usuario')])],
        ['Atrasados', $indicadores['atrasados'], 'alarm-clock', route('chamados.index', ['atrasados' => 1])],
        ['Sem responsável', $indicadores['sem_responsavel'], 'user-x', route('chamados.index', ['sem_responsavel' => 1])],
        ['Resolvidos', $indicadores['resolvidos'], 'check-circle-2', route('chamados.index', ['status_id' => \App\Models\StatusChamado::idPorSlug('resolvido')])],
    ];
@endphp
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($cartoes as [$titulo, $valor, $icone, $link])
        <a href="{{ $link }}" class="cartao transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-sm text-slate-500">{{ $titulo }}</span>
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-orange-100 text-orange-600 dark:bg-orange-500/15"><i data-lucide="{{ $icone }}" class="h-4 w-4"></i></span>
            </div>
            <p class="mt-3 text-3xl font-bold">{{ $valor }}</p>
        </a>
    @endforeach
</div>
<div class="cartao mt-6">
    <h2 class="mb-3 font-semibold">Chamados recentes</h2>
    @include('chamados._tabela', ['chamados' => $recentes])
</div>
@endsection
