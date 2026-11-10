@extends('layouts.app')

@section('titulo', 'Monitoramento de chamados')

@section('conteudo')
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="cartao">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold">Monitoramento automático</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">A rotina identifica chamados que precisam de atenção, sem alterar seus status.</p>
                </div>
                <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $configuracoes['ativo'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                    {{ $configuracoes['ativo'] ? 'Ativo' : 'Pausado' }}
                </span>
            </div>

            <form method="POST" action="{{ route('admin.monitoramento.atualizar') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid gap-4 md:grid-cols-3">
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                        <input type="checkbox" name="ativo" value="1" @checked(old('ativo', $configuracoes['ativo']))>
                        <span class="text-sm font-medium">Ativar verificações automáticas</span>
                    </label>
                    <label class="campo block">
                        <span>Intervalo (minutos)</span>
                        <input type="number" name="intervalo_minutos" min="1" max="1440" value="{{ old('intervalo_minutos', $configuracoes['intervalo_minutos']) }}" required>
                    </label>
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                        <input type="checkbox" name="notificar_supervisores" value="1" @checked(old('notificar_supervisores', $configuracoes['notificar_supervisores']))>
                        <span class="text-sm font-medium">Notificar supervisores do departamento</span>
                    </label>
                </div>

                <fieldset>
                    <legend class="mb-2 text-sm font-semibold">Dias de funcionamento</legend>
                    <div class="flex flex-wrap gap-3">
                        @php($diasSelecionados = array_map('strval', old('dias_semana', $configuracoes['dias_semana'])))
                        @foreach ($diasSemana as $numero => $dia)
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                                <input type="checkbox" name="dias_semana[]" value="{{ $numero }}" @checked(in_array((string) $numero, $diasSelecionados, true))>
                                {{ $dia }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="campo block">
                        <span>Início do expediente</span>
                        <input type="time" name="hora_inicio" value="{{ old('hora_inicio', $configuracoes['hora_inicio']) }}" required>
                    </label>
                    <label class="campo block">
                        <span>Fim do expediente</span>
                        <input type="time" name="hora_fim" value="{{ old('hora_fim', $configuracoes['hora_fim']) }}" required>
                    </label>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">Se o horário final for anterior ao inicial, o expediente é considerado noturno. Horários iguais representam funcionamento durante todo o dia.</p>

                <div>
                    <h3 class="mb-3 text-sm font-semibold">Limites para identificação de ocorrências (minutos)</h3>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <label class="campo block">
                            <span>Sem atualização</span>
                            <input type="number" name="sem_atualizacao_minutos" min="1" max="10080" value="{{ old('sem_atualizacao_minutos', $configuracoes['sem_atualizacao_minutos']) }}" required>
                        </label>
                        <label class="campo block">
                            <span>Espera na fila</span>
                            <input type="number" name="espera_fila_minutos" min="1" max="10080" value="{{ old('espera_fila_minutos', $configuracoes['espera_fila_minutos']) }}" required>
                        </label>
                        <label class="campo block">
                            <span>Chamado pendente</span>
                            <input type="number" name="pendente_minutos" min="1" max="10080" value="{{ old('pendente_minutos', $configuracoes['pendente_minutos']) }}" required>
                        </label>
                        <label class="campo block">
                            <span>Aguardando retorno do usuário</span>
                            <input type="number" name="aguardando_usuario_minutos" min="1" max="10080" value="{{ old('aguardando_usuario_minutos', $configuracoes['aguardando_usuario_minutos']) }}" required>
                        </label>
                        <label class="campo block">
                            <span>Aviso antes do vencimento do SLA</span>
                            <input type="number" name="sla_proximo_minutos" min="1" max="10080" value="{{ old('sla_proximo_minutos', $configuracoes['sla_proximo_minutos']) }}" required>
                        </label>
                    </div>
                </div>

                <button class="btn-primario" type="submit">Salvar configurações</button>
            </form>
        </section>

        <section class="cartao">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold">Execuções recentes</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Alertas ativos: {{ $alertasAtivos }}</p>
                </div>
                @if ($execucoes->isNotEmpty())
                    <p class="text-sm text-slate-500 dark:text-slate-400">Última execução: {{ $execucoes->first()->iniciada_em->format('d/m/Y H:i:s') }}</p>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase text-slate-500 dark:border-slate-700">
                        <tr><th class="py-3 pr-4">Iniciada em</th><th class="py-3 pr-4">Resultado</th><th class="py-3 pr-4">Analisados</th><th class="py-3 pr-4">Ocorrências</th><th class="py-3">Detalhe</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($execucoes as $execucao)
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="py-3 pr-4">{{ $execucao->iniciada_em->format('d/m/Y H:i:s') }}</td>
                                <td class="py-3 pr-4">{{ $execucao->sucesso ? 'Concluída' : 'Falhou' }}</td>
                                <td class="py-3 pr-4">{{ $execucao->chamados_analisados }}</td>
                                <td class="py-3 pr-4">{{ $execucao->ocorrencias_identificadas }}</td>
                                <td class="py-3">{{ $execucao->erro ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-slate-500">Nenhuma execução registrada até o momento.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
