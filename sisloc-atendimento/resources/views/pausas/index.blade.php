@extends('layouts.app')
@section('titulo', 'Pausas da equipe')
@section('conteudo')
@php
    $vagasDisponiveis = max(0, $configuracoes->limite_simultaneas - $totalEmPausa);
@endphp
<div class="mx-auto max-w-5xl space-y-6">
    @if (session('sucesso'))
        <p class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200">{{ session('sucesso') }}</p>
    @endif
    @error('pausa')
        <p class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100" role="alert">{{ $message }}</p>
    @enderror

    <section class="grid gap-4 sm:grid-cols-3">
        <div class="cartao">
            <p class="text-sm text-slate-500">Colaboradores em pausa</p>
            <p class="mt-2 text-3xl font-bold">{{ $totalEmPausa }} <span class="text-lg font-medium text-slate-400">/ {{ $configuracoes->limite_simultaneas }}</span></p>
        </div>
        <div class="cartao">
            <p class="text-sm text-slate-500">Vagas disponíveis</p>
            <p class="mt-2 text-3xl font-bold">{{ $vagasDisponiveis }}</p>
        </div>
        <div class="cartao">
            <p class="text-sm text-slate-500">Duração prevista</p>
            <p class="mt-2 text-3xl font-bold">{{ $configuracoes->duracao_minutos ? $configuracoes->duracao_minutos.' min' : 'Sem limite' }}</p>
        </div>
    </section>

    <section class="cartao">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="font-semibold">Minha pausa</h2>
                <p class="mt-1 text-sm text-slate-500">A pausa é registrada no seu histórico. Registre o retorno ao voltar.</p>
            </div>
            @if ($pausaAtiva)
                <form method="POST" action="{{ route('pausas.retornar') }}">@csrf
                    <button class="btn-primario"><i data-lucide="log-in" class="h-4 w-4"></i> Registrar retorno</button>
                </form>
            @else
                <form method="POST" action="{{ route('pausas.iniciar') }}" class="flex flex-wrap items-end gap-3">@csrf
                    <div class="min-w-52">
                        <label class="rotulo" for="motivo">Motivo da pausa</label>
                        <select class="campo" id="motivo" name="motivo" required>
                            <option value="">Selecione o motivo</option>
                            @foreach ($motivosPausa as $motivo)
                                <option value="{{ $motivo }}" @selected(old('motivo') === $motivo)>{{ $motivo }}</option>
                            @endforeach
                        </select>
                        @error('motivo')<p class="erro">{{ $message }}</p>@enderror
                    </div>
                    <button class="btn-primario"><i data-lucide="coffee" class="h-4 w-4"></i> Iniciar pausa</button>
                </form>
            @endif
        </div>
        @if ($pausaAtiva)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-orange-200 bg-orange-50 p-4 text-sm text-orange-950 dark:border-orange-900 dark:bg-orange-950/30 dark:text-orange-100">
                <div>
                    <p class="font-semibold">{{ $pausaAtiva->motivo ?? 'Pausa' }}</p>
                    <p class="mt-1">Iniciada às {{ \Illuminate\Support\Carbon::parse($pausaAtiva->iniciada_em)->format('H:i') }}. {{ $pausaAtiva->prevista_ate ? 'Retorno previsto para '.\Illuminate\Support\Carbon::parse($pausaAtiva->prevista_ate)->format('H:i').'.' : 'Sem horário previsto para retorno.' }}</p>
                </div>
                <p class="text-right">
                    <span class="block text-xs font-medium uppercase tracking-wide text-orange-800 dark:text-orange-200">Tempo decorrido</span>
                    <span class="font-mono text-2xl font-bold tabular-nums" data-pausa-cronometro="{{ \Illuminate\Support\Carbon::parse($pausaAtiva->iniciada_em)->valueOf() }}">00:00:00</span>
                </p>
            </div>
        @elseif ($vagasDisponiveis === 0)
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100">
                O limite simultâneo foi atingido. Se solicitar uma pausa agora, o sistema bloqueará o início; aguarde um retorno e acompanhe os próximos horários abaixo.
            </div>
        @endif
    </section>

    @can('cadastros.gerenciar')
        <section class="cartao">
            <div class="mb-4">
                <h2 class="font-semibold">Forçar pausa de colaborador</h2>
                <p class="mt-1 text-sm text-slate-500">A pausa começa imediatamente e segue a duração configurada, inclusive quando estiver sem limite.</p>
            </div>
            <form method="POST" action="{{ route('pausas.forcar') }}" class="grid gap-4 sm:grid-cols-3">@csrf
                <div>
                    <label class="rotulo" for="colaborador_pausa">Colaborador</label>
                    <select class="campo" id="colaborador_pausa" name="usuario_id" required>
                        <option value="">Selecione o colaborador</option>
                        @foreach ($colaboradores as $colaborador)
                            <option value="{{ $colaborador->id }}" @selected((string) old('usuario_id') === (string) $colaborador->id)>{{ $colaborador->nome }}</option>
                        @endforeach
                    </select>
                    @error('usuario_id')<p class="erro">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="rotulo" for="motivo_administrativo">Categoria da pausa</label>
                    <select class="campo" id="motivo_administrativo" name="motivo" required>
                        <option value="">Selecione a categoria</option>
                        @foreach ($motivosPausa as $motivo)
                            <option value="{{ $motivo }}" @selected(old('motivo') === $motivo)>{{ $motivo }}</option>
                        @endforeach
                    </select>
                    @error('motivo')<p class="erro">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-end">
                    <button class="btn-primario w-full justify-center"><i data-lucide="coffee" class="h-4 w-4"></i> Forçar pausa</button>
                </div>
            </form>
        </section>

        <section class="cartao">
            <div class="mb-4">
                <h2 class="font-semibold">Categorias de pausa</h2>
                <p class="mt-1 text-sm text-slate-500">Crie categorias para os colaboradores e para as pausas iniciadas pela administração.</p>
            </div>
            <form method="POST" action="{{ route('pausas.motivos.criar') }}" class="flex flex-wrap items-end gap-3">@csrf
                <div class="min-w-64 flex-1">
                    <label class="rotulo" for="novo_motivo">Nova categoria</label>
                    <input class="campo" id="novo_motivo" name="nome" maxlength="80" value="{{ old('nome') }}" required>
                    @error('nome')<p class="erro">{{ $message }}</p>@enderror
                </div>
                <button class="btn-primario"><i data-lucide="plus" class="h-4 w-4"></i> Criar categoria</button>
            </form>
            <ul class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                @foreach ($motivosPausa as $motivo)
                    <li class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-700">{{ $motivo }}</li>
                @endforeach
            </ul>
        </section>
    @endcan

    <section class="cartao">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2 class="font-semibold">Próximos retornos</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $visaoAdministrativa ? 'Todas as pausas ativas, ordenadas pelo retorno previsto.' : 'Os três colaboradores com retorno previsto mais próximo.' }}</p>
            </div>
            <span class="text-xs text-slate-400">{{ $totalEmPausa }} em pausa agora</span>
        </div>
        @forelse ($proximas as $pausa)
            @php($retorno = $pausa->prevista_ate ? \Illuminate\Support\Carbon::parse($pausa->prevista_ate) : null)
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 py-3 dark:border-slate-800">
                <div>
                    <p class="text-sm font-semibold">{{ $pausa->nome }}</p>
                    @if ($visaoGerencial)
                        <p class="text-xs text-slate-500">{{ $pausa->motivo ?? 'Pausa' }} · {{ $pausa->departamento ?? 'Sem departamento' }} · desde {{ \Illuminate\Support\Carbon::parse($pausa->iniciada_em)->format('H:i') }}</p>
                        <p class="mt-1 font-mono text-xs font-semibold tabular-nums text-orange-700 dark:text-orange-300" data-pausa-cronometro="{{ \Illuminate\Support\Carbon::parse($pausa->iniciada_em)->valueOf() }}" data-sufixo=" decorridos">00:00:00 decorridos</p>
                    @endif
                </div>
                @if ($visaoGerencial || $visaoAdministrativa)
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        @if ($visaoGerencial)
                            <p class="text-right text-sm font-semibold {{ $retorno?->isPast() ? 'text-amber-700 dark:text-amber-300' : 'text-slate-700 dark:text-slate-200' }}">
                                {{ $retorno?->format('H:i') ?? 'Sem previsão' }}
                                <span class="block text-xs font-normal text-slate-500">{{ $retorno ? ($retorno->isPast() ? 'retorno previsto · atrasado' : $retorno->diffForHumans()) : 'pausa sem limite de tempo' }}</span>
                            </p>
                        @endif
                        @if ($visaoAdministrativa)
                            <form method="POST" action="{{ route('pausas.encerrar-admin', $pausa->id) }}" onsubmit="return confirm('Encerrar esta pausa?')">@csrf
                                <button class="btn-neutro" title="Encerrar pausa de {{ $pausa->nome }}"><i data-lucide="log-out" class="h-4 w-4"></i><span>Encerrar pausa</span></button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <p class="border-t border-slate-100 py-4 text-sm text-slate-500 dark:border-slate-800">Ninguém está em pausa no momento.</p>
        @endforelse
    </section>

    <section class="cartao">
        <div class="mb-4">
            <h2 class="font-semibold">{{ $visaoAdministrativa ? 'Histórico de pausas da equipe' : 'Meu histórico de pausas' }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $visaoAdministrativa ? 'Registros de pausa de todos os colaboradores.' : 'Somente você pode consultar seus registros de pausa.' }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs text-slate-500 dark:border-slate-700">
                    <tr>
                        @if ($visaoAdministrativa)
                            <th class="px-3 py-2 font-medium">Colaborador</th>
                            <th class="px-3 py-2 font-medium">Iniciado por</th>
                        @endif
                        <th class="px-3 py-2 font-medium">Início</th>
                        <th class="px-3 py-2 font-medium">Motivo</th>
                        <th class="px-3 py-2 font-medium">Retorno previsto</th>
                        <th class="px-3 py-2 font-medium">Retorno registrado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($historico as $pausa)
                        <tr>
                            @if ($visaoAdministrativa)
                                <td class="px-3 py-3">
                                    <span class="font-medium">{{ $pausa->nome }}</span>
                                    @if ($pausa->departamento)
                                        <span class="block text-xs text-slate-500">{{ $pausa->departamento }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">{{ $pausa->iniciada_por ?? '—' }}</td>
                            @endif
                            <td class="whitespace-nowrap px-3 py-3">{{ \Illuminate\Support\Carbon::parse($pausa->iniciada_em)->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-3">{{ $pausa->motivo ?? 'Pausa' }}</td>
                            <td class="whitespace-nowrap px-3 py-3">{{ $pausa->prevista_ate ? \Illuminate\Support\Carbon::parse($pausa->prevista_ate)->format('d/m/Y H:i') : 'Sem limite' }}</td>
                            <td class="whitespace-nowrap px-3 py-3">{{ $pausa->retornada_em ? \Illuminate\Support\Carbon::parse($pausa->retornada_em)->format('d/m/Y H:i') : 'Em andamento' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $visaoAdministrativa ? 6 : 4 }}" class="px-3 py-4 text-slate-500">Nenhuma pausa registrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($historico->hasPages())
            <div class="mt-4">{{ $historico->links() }}</div>
        @endif
    </section>

    @can('cadastros.gerenciar')
        <section class="cartao">
            <div class="mb-4">
                <h2 class="font-semibold">Limites de pausa · administração</h2>
                <p class="mt-1 text-sm text-slate-500">Defina quantas pessoas podem pausar ao mesmo tempo e, se necessário, o tempo máximo de cada pausa.</p>
            </div>
            <form method="POST" action="{{ route('admin.pausas.atualizar') }}" class="grid gap-4 sm:grid-cols-4" data-configuracao-pausa>
                @csrf @method('PUT')
                <div>
                    <label class="rotulo" for="limite_simultaneas">Limite simultâneo</label>
                    <input class="campo" id="limite_simultaneas" name="limite_simultaneas" type="number" min="1" max="200" value="{{ old('limite_simultaneas', $configuracoes->limite_simultaneas) }}" required>
                    @error('limite_simultaneas')<p class="erro">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="rotulo" for="duracao_minutos">Tempo máximo (minutos)</label>
                    <input class="campo" id="duracao_minutos" name="duracao_minutos" type="number" min="1" max="240" value="{{ old('duracao_minutos', $configuracoes->duracao_minutos) }}" @required(! old('sem_limite', is_null($configuracoes->duracao_minutos))) @disabled(old('sem_limite', is_null($configuracoes->duracao_minutos))) data-duracao-pausa>
                    @error('duracao_minutos')<p class="erro">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2 pb-2 text-sm text-slate-700 dark:text-slate-200">
                    <input class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500" id="sem_limite" name="sem_limite" type="checkbox" value="1" @checked(old('sem_limite', is_null($configuracoes->duracao_minutos))) data-sem-limite-pausa>
                    Sem limite de tempo
                </label>
                <div class="flex items-end">
                    <button class="btn-primario w-full justify-center">Salvar limites</button>
                </div>
            </form>
        </section>
    @endcan
</div>
<script>
    const limitePausa = document.querySelector('[data-sem-limite-pausa]');
    const duracaoPausa = document.querySelector('[data-duracao-pausa]');
    if (limitePausa && duracaoPausa) {
        limitePausa.addEventListener('change', () => {
            duracaoPausa.disabled = limitePausa.checked;
            duracaoPausa.required = !limitePausa.checked;
            if (limitePausa.checked) duracaoPausa.value = '';
        });
    }
    const atualizarCronometrosPausa = () => {
        document.querySelectorAll('[data-pausa-cronometro]').forEach((cronometro) => {
            const segundos = Math.max(0, Math.floor((Date.now() - Number(cronometro.dataset.pausaCronometro)) / 1000));
            const horas = String(Math.floor(segundos / 3600)).padStart(2, '0');
            const minutos = String(Math.floor((segundos % 3600) / 60)).padStart(2, '0');
            const resto = String(segundos % 60).padStart(2, '0');
            cronometro.textContent = `${horas}:${minutos}:${resto}${cronometro.dataset.sufixo ?? ''}`;
        });
    };
    atualizarCronometrosPausa();
    window.setInterval(atualizarCronometrosPausa, 1000);
</script>
@endsection
