<?php

namespace App\Http\Controllers;

use App\Models\Chamado;
use App\Models\Departamento;
use App\Models\Prioridade;
use App\Models\StatusChamado;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FilaController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'busca' => ['nullable', 'string', 'max:160'],
            'departamento_id' => ['nullable', 'integer', Rule::exists('departamentos', 'id')->where('ativo', true)],
            'prioridade_id' => ['nullable', 'integer', Rule::exists('prioridades', 'id')->where('ativo', true)],
            'status_id' => ['nullable', 'integer', Rule::exists('status_chamado', 'id')->where('final', false)],
            'sem_responsavel' => ['nullable', 'boolean'],
            'ordem' => ['nullable', Rule::in(['antigos', 'recentes', 'prioridade', 'espera'])],
        ]);
        $ordem = $request->string('ordem')->value();

        $consulta = Chamado::visiveisPara($request->user())
            ->whereHas('status', fn ($query) => $query->where('final', false))
            ->with(['status', 'prioridade', 'solicitante', 'agente', 'departamento', 'categoria'])
            ->when($request->filled('busca'), function ($query) use ($request) {
                $termo = '%'.$request->string('busca').'%';
                $query->where(fn ($query) => $query
                    ->where('numero', 'like', $termo)
                    ->orWhere('empresa_cnpj', 'like', $termo)
                    ->orWhere('empresa_nome', 'like', $termo)
                    ->orWhere('solicitante_nome', 'like', $termo)
                    ->orWhereHas('solicitante', fn ($solicitante) => $solicitante->where('nome', 'like', $termo))
                    ->orWhereHas('categoria', fn ($categoria) => $categoria->where('nome', 'like', $termo)));
            })
            ->when($request->filled('departamento_id'), fn ($query) => $query->where('departamento_id', $request->integer('departamento_id')))
            ->when($request->filled('prioridade_id'), fn ($query) => $query->where('prioridade_id', $request->integer('prioridade_id')))
            ->when($request->boolean('sem_responsavel'), fn ($query) => $query->whereNull('agente_id'))
            ->when($request->filled('status_id'), fn ($query) => $query->where('status_id', $request->integer('status_id')));

        match ($ordem) {
            'recentes' => $consulta->orderByDesc('created_at'),
            'prioridade' => $consulta->join('prioridades as prioridade_fila', 'prioridade_fila.id', '=', 'chamados.prioridade_id')
                ->select('chamados.*')->orderByDesc('prioridade_fila.nivel')->orderBy('chamados.created_at'),
            'espera', 'antigos' => $consulta->orderBy('created_at'),
            default => $consulta->orderBy('created_at'),
        };

        return view('fila.index', [
            'chamados' => $consulta->paginate(25)->withQueryString(),
            'departamentos' => Departamento::where('ativo', true)->orderBy('nome')->get(),
            'prioridades' => Prioridade::where('ativo', true)->orderBy('nivel')->get(),
            'statusLista' => StatusChamado::where('ativo', true)->where('final', false)->orderBy('ordem')->get(),
            'ordem' => $ordem ?: 'antigos',
        ]);
    }
}
