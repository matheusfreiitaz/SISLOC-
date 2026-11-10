@extends('layouts.app')
@section('titulo', 'Notificações')
@section('conteudo')
<div class="cartao">
    <div class="mb-4 flex justify-end"><form method="POST" action="{{ route('notificacoes.ler-todas') }}">@csrf<button class="btn-neutro">Marcar todas como lidas</button></form></div>
    <ul class="divide-y divide-slate-100 dark:divide-slate-800">
    @forelse ($notificacoes as $n)
        <li class="flex items-start justify-between gap-3 py-3">
            <div class="{{ $n->lida_em ? 'opacity-60' : '' }}">
                <p class="text-sm font-semibold">@unless ($n->lida_em)<span class="mr-1 inline-block h-2 w-2 rounded-full bg-orange-500"></span>@endunless{{ $n->titulo }}</p>
                <p class="text-sm text-slate-500">{{ $n->mensagem }}</p>
                <p class="text-xs text-slate-400">{{ $n->created_at->diffForHumans() }}</p>
            </div>
            @unless ($n->lida_em)<form method="POST" action="{{ route('notificacoes.ler', $n) }}">@csrf<button class="btn-neutro">{{ $n->url ? 'Abrir' : 'Marcar como lida' }}</button></form>@endunless
        </li>
    @empty
        <li class="py-6 text-center text-sm text-slate-500">Nenhuma notificação.</li>
    @endforelse
    </ul>
    <div class="mt-4">{{ $notificacoes->links() }}</div>
</div>
@endsection
