<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChamadoRequest;
use App\Http\Requests\FinalizarChamadoRequest;
use App\Http\Requests\ResponderChamadoRequest;
use App\Models\AnexoChamado;
use App\Models\Categoria;
use App\Models\Chamado;
use App\Models\Departamento;
use App\Models\MotivoFinalizacao;
use App\Models\Prioridade;
use App\Models\StatusChamado;
use App\Models\Usuario;
use App\Services\ChamadoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChamadoController extends Controller
{
    public function __construct(private ChamadoService $servico) {}

    public function index(Request $r)
    {
        $consulta = Chamado::visiveisPara($r->user())
            ->with(['status', 'prioridade', 'solicitante', 'agente', 'departamento'])
            ->when($r->filled('busca'), function ($q) use ($r) {
                $termo = '%'.$r->string('busca').'%';
                $q->where(fn ($q) => $q->where('numero', 'like', $termo)->orWhere('assunto', 'like', $termo)
                    ->orWhereHas('solicitante', fn ($s) => $s->where('nome', 'like', $termo)->orWhere('email', 'like', $termo))
                    ->orWhereHas('agente', fn ($a) => $a->where('nome', 'like', $termo)));
            })
            ->when($r->filled('status_id'), fn ($q) => $q->where('status_id', $r->integer('status_id')))
            ->when($r->filled('prioridade_id'), fn ($q) => $q->where('prioridade_id', $r->integer('prioridade_id')))
            ->when($r->filled('departamento_id'), fn ($q) => $q->where('departamento_id', $r->integer('departamento_id')))
            ->when($r->filled('agente_id'), fn ($q) => $q->where('agente_id', $r->integer('agente_id')))
            ->when($r->filled('de'), fn ($q) => $q->whereDate('created_at', '>=', $r->date('de')))
            ->when($r->filled('ate'), fn ($q) => $q->whereDate('created_at', '<=', $r->date('ate')))
            ->when($r->boolean('atrasados'), fn ($q) => $q->atrasados())
            ->when($r->boolean('sem_responsavel'), fn ($q) => $q->whereNull('agente_id'));

        return view('chamados.index', [
            'chamados' => $consulta->latest()->paginate(15)->withQueryString(),
            'statusLista' => StatusChamado::orderBy('ordem')->get(),
            'prioridades' => Prioridade::orderBy('nivel')->get(),
            'departamentos' => Departamento::orderBy('nome')->get(),
            'agentes' => Usuario::whereHas('perfil.permissoes', fn ($q) => $q->where('chave', 'chamados.finalizar'))->orderBy('nome')->get(),
        ]);
    }

    public function create()
    {
        return view('chamados.create', [
            'departamentos' => Departamento::where('ativo', true)->orderBy('nome')->get(),
            'categorias' => Categoria::where('ativo', true)->with('subcategorias')->whereNull('categoria_pai_id')->orderBy('nome')->get(),
            'prioridades' => Prioridade::where('ativo', true)->orderBy('nivel')->get(),
            'solicitantes' => auth()->user()->can('chamados.ver_todos') ? Usuario::where('ativo', true)->orderBy('nome')->get() : collect(),
        ]);
    }

    public function store(ChamadoRequest $requisicao): RedirectResponse
    {
        $dados = $requisicao->validated();
        if (! $requisicao->user()->can('chamados.ver_todos')) {
            unset($dados['solicitante_id']); // solicitante comum só abre chamado para si
        }

        $chamado = $this->servico->criar($requisicao->user(), $dados, $requisicao->file('anexos', []));

        return redirect()->route('chamados.show', $chamado)->with('sucesso', "Chamado {$chamado->numero} aberto com sucesso.");
    }

    public function show(Chamado $chamado)
    {
        $this->authorize('ver', $chamado);
        $usuario = auth()->user();

        $chamado->load(['solicitante.departamento', 'agente', 'departamento', 'categoria', 'subcategoria', 'prioridade', 'status',
            'anexos', 'historico.usuario', 'finalizacao.motivo', 'finalizacao.usuario']);

        $interacoes = $chamado->interacoes()->with(['usuario', 'anexos'])
            ->when(! $usuario->can('verNotasInternas', $chamado), fn ($q) => $q->where('tipo', '!=', 'nota_interna'))
            ->get();

        return view('chamados.show', [
            'chamado' => $chamado,
            'interacoes' => $interacoes,
            'statusLista' => StatusChamado::where('ativo', true)->where('slug', '!=', 'resolvido')->orderBy('ordem')->get(),
            'prioridades' => Prioridade::where('ativo', true)->orderBy('nivel')->get(),
            'departamentos' => Departamento::where('ativo', true)->orderBy('nome')->get(),
            'agentes' => Usuario::where('ativo', true)->whereHas('perfil.permissoes', fn ($q) => $q->where('chave', 'chamados.finalizar'))->orderBy('nome')->get(),
            'motivos' => MotivoFinalizacao::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function atualizar(Request $r, Chamado $chamado): RedirectResponse
    {
        $this->authorize('editar', $chamado);
        $dados = $r->validate([
            'assunto' => ['required', 'string', 'max:200'],
            'descricao' => ['required', 'string', 'max:10000'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'subcategoria_id' => ['nullable', 'exists:categorias,id'],
        ]);
        $this->servico->editar($chamado, $r->user(), $dados);

        return back()->with('sucesso', 'Chamado atualizado.');
    }

    public function atribuir(Request $r, Chamado $chamado): RedirectResponse
    {
        $this->authorize('atribuir', $chamado);
        $dados = $r->validate(['agente_id' => ['nullable', 'exists:usuarios,id']]);
        $agente = isset($dados['agente_id']) ? Usuario::findOrFail($dados['agente_id']) : null;
        $this->servico->atribuir($chamado, $agente, $r->user());

        return back()->with('sucesso', 'Responsável atualizado.');
    }

    public function transferir(Request $r, Chamado $chamado): RedirectResponse
    {
        $this->authorize('transferir', $chamado);
        $dados = $r->validate(['departamento_id' => ['required', 'exists:departamentos,id']]);
        $this->servico->transferirDepartamento($chamado, $dados['departamento_id'], $r->user());

        return redirect()->route('chamados.index')->with('sucesso', 'Chamado transferido de departamento.');
    }

    public function prioridade(Request $r, Chamado $chamado): RedirectResponse
    {
        $this->authorize('editar', $chamado);
        $dados = $r->validate(['prioridade_id' => ['required', 'exists:prioridades,id']]);
        $this->servico->alterarPrioridade($chamado, $dados['prioridade_id'], $r->user());

        return back()->with('sucesso', 'Prioridade alterada.');
    }

    public function status(Request $r, Chamado $chamado): RedirectResponse
    {
        $this->authorize('atribuir', $chamado);
        $dados = $r->validate(['status_id' => ['required', 'exists:status_chamado,id']]);
        $this->servico->alterarStatus($chamado, $dados['status_id'], $r->user());

        return back()->with('sucesso', 'Status alterado.');
    }

    public function responder(ResponderChamadoRequest $requisicao, Chamado $chamado): RedirectResponse
    {
        $d = $requisicao->validated();
        if ($d['tipo'] === 'nota_interna') {
            $this->authorize('verNotasInternas', $chamado);
        }
        $this->servico->responder($chamado, $requisicao->user(), $d['mensagem'], $d['tipo'], $requisicao->file('anexos', []));

        return back()->with('sucesso', 'Mensagem registrada.');
    }

    public function finalizar(FinalizarChamadoRequest $requisicao, Chamado $chamado): RedirectResponse
    {
        $d = $requisicao->validated();
        $this->servico->finalizar($chamado, $requisicao->user(), $d['motivo_id'], $d['descricao_solucao'], $d['observacoes'] ?? null);

        return back()->with('sucesso', 'Atendimento finalizado.');
    }

    public function baixarAnexo(AnexoChamado $anexo)
    {
        $this->authorize('ver', $anexo->chamado);

        return Storage::disk('local')->download($anexo->caminho, $anexo->nome_original);
    }
}
