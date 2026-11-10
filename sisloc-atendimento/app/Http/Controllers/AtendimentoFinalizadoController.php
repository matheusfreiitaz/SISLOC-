<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Chamado;
use App\Models\Departamento;
use App\Models\MotivoFinalizacao;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AtendimentoFinalizadoController extends Controller
{
    public function index(Request $request): View
    {
        return view('finalizados.index', [
            'chamados' => $this->consulta($request)->paginate(25)->withQueryString(),
            'departamentos' => Departamento::orderBy('nome')->get(),
            'categorias' => Categoria::orderBy('nome')->get(),
            'motivos' => MotivoFinalizacao::orderBy('nome')->get(),
            'agentes' => Usuario::where('ativo', true)->whereHas('perfil.permissoes', fn ($query) => $query->where('chave', 'chamados.finalizar'))->orderBy('nome')->get(),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $chamados = $this->consulta($request)->limit(10000)->get();

        return response()->streamDownload(function () use ($chamados) {
            $saida = fopen('php://output', 'w');
            fwrite($saida, "\xEF\xBB\xBF");
            fputcsv($saida, ['Protocolo', 'Empresa', 'CNPJ', 'Solicitante', 'Categoria', 'Agente', 'Departamento',
                'Abertura', 'Finalização', 'Tempo total (min)', 'Motivo', 'Solução'], ';');

            foreach ($chamados as $chamado) {
                $valores = [
                    $chamado->numero,
                    $chamado->empresa_nome ?: '—',
                    $chamado->cnpjFormatado() ?: '—',
                    $chamado->nomeSolicitante(),
                    $chamado->categoria?->nome ?? '—',
                    $chamado->agente?->nome ?? '—',
                    $chamado->departamento->nome,
                    $chamado->created_at->format('d/m/Y H:i'),
                    $chamado->finalizado_em?->format('d/m/Y H:i') ?? '—',
                    $chamado->finalizado_em ? $chamado->created_at->diffInMinutes($chamado->finalizado_em) : '',
                    $chamado->finalizacao?->motivo?->nome ?? '—',
                    $chamado->finalizacao?->descricao_solucao ?? '—',
                ];
                fputcsv($saida, array_map(fn ($valor) => $this->protegerPlanilha((string) $valor), $valores), ';');
            }

            fclose($saida);
        }, 'atendimentos-finalizados.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function consulta(Request $request): Builder
    {
        $request->validate([
            'busca' => ['nullable', 'string', 'max:160'],
            'departamento_id' => ['nullable', 'integer', 'exists:departamentos,id'],
            'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'motivo_id' => ['nullable', 'integer', 'exists:motivos_finalizacao,id'],
            'agente_id' => ['nullable', 'integer', Rule::exists('usuarios', 'id')->where('ativo', true)],
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date', 'after_or_equal:de'],
        ]);

        return Chamado::visiveisPara($request->user())
            ->whereHas('status', fn ($query) => $query->where('final', true))
            ->with(['solicitante', 'agente', 'departamento', 'categoria', 'status', 'finalizacao.motivo', 'finalizacao.usuario'])
            ->when($request->filled('busca'), function ($query) use ($request) {
                $termo = '%'.$request->string('busca').'%';
                $query->where(fn ($query) => $query
                    ->where('numero', 'like', $termo)
                    ->orWhere('empresa_cnpj', 'like', $termo)
                    ->orWhere('empresa_nome', 'like', $termo)
                    ->orWhere('solicitante_nome', 'like', $termo)
                    ->orWhereHas('solicitante', fn ($solicitante) => $solicitante->where('nome', 'like', $termo)->orWhere('email', 'like', $termo))
                    ->orWhereHas('agente', fn ($agente) => $agente->where('nome', 'like', $termo))
                    ->orWhereHas('finalizacao.motivo', fn ($motivo) => $motivo->where('nome', 'like', $termo)));
            })
            ->when($request->filled('departamento_id'), fn ($query) => $query->where('departamento_id', $request->integer('departamento_id')))
            ->when($request->filled('categoria_id'), fn ($query) => $query->where('categoria_id', $request->integer('categoria_id')))
            ->when($request->filled('agente_id'), fn ($query) => $query->where('agente_id', $request->integer('agente_id')))
            ->when($request->filled('motivo_id'), fn ($query) => $query->whereHas('finalizacao', fn ($finalizacao) => $finalizacao->where('motivo_id', $request->integer('motivo_id'))))
            ->when($request->filled('de'), fn ($query) => $query->whereDate('finalizado_em', '>=', $request->date('de')))
            ->when($request->filled('ate'), fn ($query) => $query->whereDate('finalizado_em', '<=', $request->date('ate')))
            ->orderByDesc('finalizado_em')->orderByDesc('id');
    }

    private function protegerPlanilha(string $valor): string
    {
        return preg_match('/^[=+\-@]/', $valor) ? "'".$valor : $valor;
    }
}
