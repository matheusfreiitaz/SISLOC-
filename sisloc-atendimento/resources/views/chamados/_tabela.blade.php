<div class="overflow-x-auto">
<table class="tabela">
    <thead><tr><th>Nº</th><th>Assunto</th><th>Solicitante</th><th>Prioridade</th><th>Status</th><th>Prazo</th></tr></thead>
    <tbody>
    @forelse ($chamados as $c)
        <tr>
            <td class="font-mono text-xs"><a class="text-orange-600 hover:underline" href="{{ route('chamados.show', $c) }}">{{ $c->numero }}</a></td>
            <td>{{ \Illuminate\Support\Str::limit($c->assunto, 50) }}</td>
            <td>{{ $c->nomeSolicitante() }}</td>
            <td><x-selo-status :cor="$c->prioridade->cor" :texto="$c->prioridade->nome" /></td>
            <td><x-selo-status :cor="$c->status->cor" :texto="$c->status->nome" /></td>
            <td class="{{ $c->estaAtrasado() ? 'font-semibold text-red-600' : '' }}">{{ $c->prazo_em?->format('d/m/Y H:i') }} {{ $c->estaAtrasado() ? '· atrasado' : '' }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="py-6 text-center text-slate-500">Nenhum chamado encontrado.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
