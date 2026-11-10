@extends('layouts.app')
@section('titulo', 'Chamado '.$chamado->numero)
@section('conteudo')
@php
    $finalizado = $chamado->estaFinalizado();
    $tempoResposta = $chamado->primeira_resposta_em ? $chamado->created_at->diffForHumans($chamado->primeira_resposta_em, true) : null;
    $tempoTotal = $chamado->finalizado_em ? $chamado->created_at->diffForHumans($chamado->finalizado_em, true) : $chamado->created_at->diffForHumans(null, true);
@endphp
<div class="grid gap-6 xl:grid-cols-3">
<div class="space-y-6 xl:col-span-2">
    <div class="cartao">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="font-mono text-xs text-slate-500">{{ $chamado->numero }}</p>
                <h2 class="text-xl font-bold">{{ $chamado->assunto }}</h2>
            </div>
            <div class="flex gap-2">
                <x-selo-status :cor="$chamado->prioridade->cor" :texto="$chamado->prioridade->nome" />
                <x-selo-status :cor="$chamado->status->cor" :texto="$chamado->status->nome" />
            </div>
        </div>
        <p class="mt-4 whitespace-pre-line text-sm">{{ $chamado->descricao }}</p>
        @if ($chamado->anexos->whereNull('interacao_id')->isNotEmpty())
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($chamado->anexos->whereNull('interacao_id') as $a)
                    <a class="btn-neutro" href="{{ route('anexos.baixar', $a) }}"><i data-lucide="paperclip" class="h-4 w-4"></i> {{ $a->nome_original }}</a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($finalizado && $chamado->finalizacao)
        <div class="cartao border-emerald-300 dark:border-emerald-800">
            <h3 class="mb-1 font-semibold text-emerald-700 dark:text-emerald-400">Atendimento finalizado</h3>
            <p class="text-sm"><b>Motivo:</b> {{ $chamado->finalizacao->motivo->nome }}</p>
            <p class="mt-1 whitespace-pre-line text-sm"><b>Solução:</b> {{ $chamado->finalizacao->descricao_solucao }}</p>
            @if ($chamado->finalizacao->observacoes)<p class="mt-1 whitespace-pre-line text-sm"><b>Observações:</b> {{ $chamado->finalizacao->observacoes }}</p>@endif
            <p class="mt-2 text-xs text-slate-500">Por {{ $chamado->finalizacao->usuario->nome }} em {{ $chamado->finalizacao->created_at->format('d/m/Y H:i') }}</p>
        </div>
    @endif

    <div class="cartao">
        <h3 class="mb-4 font-semibold">Interações</h3>
        <ol class="space-y-4 border-l-2 border-orange-200 pl-4 dark:border-orange-500/30">
            @forelse ($interacoes as $i)
                <li class="relative">
                    <span class="absolute -left-[25px] top-1 h-3 w-3 rounded-full {{ $i->tipo === 'nota_interna' ? 'bg-slate-400' : 'bg-orange-500' }}"></span>
                    <div class="rounded-xl p-3 text-sm {{ $i->tipo === 'nota_interna' ? 'bg-amber-50 dark:bg-amber-950/40' : 'bg-slate-100 dark:bg-slate-800' }}">
                        <div class="mb-1 flex justify-between text-xs text-slate-500">
                            <span><b>{{ $i->usuario->nome }}</b> {{ $i->tipo === 'nota_interna' ? '· nota interna' : '' }}</span>
                            <span>{{ $i->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <p class="whitespace-pre-line">{{ $i->mensagem }}</p>
                        @foreach ($i->anexos as $a)
                            <a class="mt-2 inline-flex items-center gap-1 text-xs text-orange-600 hover:underline" href="{{ route('anexos.baixar', $a) }}"><i data-lucide="paperclip" class="h-3 w-3"></i> {{ $a->nome_original }}</a>
                        @endforeach
                    </div>
                </li>
            @empty
                <li class="text-sm text-slate-500">Ainda não há interações.</li>
            @endforelse
        </ol>

        @can('responder', $chamado)
            @unless ($finalizado)
            <form method="POST" action="{{ route('chamados.responder', $chamado) }}" enctype="multipart/form-data" class="mt-6 space-y-3">
                @csrf
                <textarea class="campo" name="mensagem" rows="4" placeholder="Escreva sua resposta…" required>{{ old('mensagem') }}</textarea>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <input type="file" name="anexos[]" multiple class="text-xs">
                    <div class="flex items-center gap-2">
                        @can('verNotasInternas', $chamado)
                            <select name="tipo" class="campo w-auto"><option value="resposta">Resposta ao solicitante</option><option value="nota_interna">Nota interna</option></select>
                        @else
                            <input type="hidden" name="tipo" value="resposta">
                        @endcan
                        <button class="btn-primario"><i data-lucide="send" class="h-4 w-4"></i> Enviar</button>
                    </div>
                </div>
            </form>
            @endunless
        @endcan
    </div>

    <div class="cartao">
        <h3 class="mb-3 font-semibold">Histórico de alterações</h3>
        <ul class="space-y-2 text-sm">
            @foreach ($chamado->historico as $h)
                <li class="flex justify-between gap-3 border-b border-slate-100 pb-2 dark:border-slate-800">
                    <span><b>{{ ucfirst($h->campo) }}</b>:
                        @if ($h->campo === 'chamado') {{ $h->valor_novo }}
                        @else {{ $h->valor_anterior ?: '—' }} → {{ $h->valor_novo ?: '—' }} @endif
                        <span class="text-slate-500">por {{ $h->usuario?->nome ?? 'sistema' }}</span></span>
                    <span class="shrink-0 text-xs text-slate-500">{{ $h->created_at->format('d/m/Y H:i') }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>

<aside class="space-y-6">
    <div class="cartao text-sm">
        <h3 class="mb-3 font-semibold">Solicitante</h3>
        <p class="font-medium">{{ $chamado->solicitante->nome }}</p>
        <p class="text-slate-500">{{ $chamado->solicitante->email }}</p>
        <h3 class="mb-2 mt-5 font-semibold">Informações</h3>
        <dl class="space-y-1">
            <div class="flex justify-between"><dt class="text-slate-500">Departamento</dt><dd>{{ $chamado->departamento->nome }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Categoria</dt><dd>{{ $chamado->categoria?->nome ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Subcategoria</dt><dd>{{ $chamado->subcategoria?->nome ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Responsável</dt><dd>{{ $chamado->agente?->nome ?? 'Sem responsável' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Aberto em</dt><dd>{{ $chamado->created_at->format('d/m/Y H:i') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Atualizado em</dt><dd>{{ $chamado->updated_at->format('d/m/Y H:i') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Prazo</dt><dd class="{{ $chamado->estaAtrasado() ? 'font-semibold text-red-600' : '' }}">{{ $chamado->prazo_em?->format('d/m/Y H:i') }}</dd></div>
        </dl>
        <h3 class="mb-2 mt-5 font-semibold">Tempos</h3>
        <dl class="space-y-1">
            <div class="flex justify-between"><dt class="text-slate-500">Primeira resposta</dt><dd>{{ $tempoResposta ?? 'Aguardando' }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">{{ $finalizado ? 'Tempo de resolução' : 'Tempo em aberto' }}</dt><dd>{{ $tempoTotal }}</dd></div>
        </dl>
    </div>

    @unless ($finalizado)
    <div class="cartao space-y-4 text-sm">
        <h3 class="font-semibold">Ações</h3>
        @can('atribuir', $chamado)
            <form method="POST" action="{{ route('chamados.atribuir', $chamado) }}" class="flex gap-2">@csrf
                <select name="agente_id" class="campo"><option value="">Sem responsável</option>
                    @foreach ($agentes as $a)<option value="{{ $a->id }}" @selected($chamado->agente_id == $a->id)>{{ $a->nome }}</option>@endforeach</select>
                <button class="btn-neutro">Atribuir</button>
            </form>
            <form method="POST" action="{{ route('chamados.status', $chamado) }}" class="flex gap-2">@csrf
                <select name="status_id" class="campo">@foreach ($statusLista as $s)<option value="{{ $s->id }}" @selected($chamado->status_id == $s->id)>{{ $s->nome }}</option>@endforeach</select>
                <button class="btn-neutro">Status</button>
            </form>
        @endcan
        @can('editar', $chamado)
            <form method="POST" action="{{ route('chamados.prioridade', $chamado) }}" class="flex gap-2">@csrf
                <select name="prioridade_id" class="campo">@foreach ($prioridades as $p)<option value="{{ $p->id }}" @selected($chamado->prioridade_id == $p->id)>{{ $p->nome }}</option>@endforeach</select>
                <button class="btn-neutro">Prioridade</button>
            </form>
        @endcan
        @can('transferir', $chamado)
            <form method="POST" action="{{ route('chamados.transferir', $chamado) }}" class="flex gap-2" onsubmit="return confirm('Transferir o chamado para outro departamento?')">@csrf
                <select name="departamento_id" class="campo">@foreach ($departamentos as $d)<option value="{{ $d->id }}" @selected($chamado->departamento_id == $d->id)>{{ $d->nome }}</option>@endforeach</select>
                <button class="btn-neutro">Transferir</button>
            </form>
        @endcan
    </div>

    @can('finalizar', $chamado)
    <form method="POST" action="{{ route('chamados.finalizar', $chamado) }}" class="cartao space-y-3 text-sm">@csrf
        <h3 class="font-semibold">Finalizar atendimento</h3>
        <div><label class="rotulo">Motivo</label><select name="motivo_id" class="campo" required><option value="">Selecione…</option>
            @foreach ($motivos as $m)<option value="{{ $m->id }}" @selected(old('motivo_id') == $m->id)>{{ $m->nome }}</option>@endforeach</select></div>
        <div><label class="rotulo">Descrição da solução aplicada</label><textarea name="descricao_solucao" rows="3" class="campo" required>{{ old('descricao_solucao') }}</textarea></div>
        <div><label class="rotulo">Observações (opcional)</label><textarea name="observacoes" rows="2" class="campo">{{ old('observacoes') }}</textarea></div>
        <button class="btn-primario w-full justify-center" onclick="return confirm('Confirmar a finalização do atendimento?')">Confirmar finalização</button>
    </form>
    @endcan
    @endunless
</aside>
</div>
@endsection
