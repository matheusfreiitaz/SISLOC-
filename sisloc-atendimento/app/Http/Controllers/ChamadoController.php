<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChamadoRequest;
use App\Http\Requests\FinalizarChamadoRequest;
use App\Http\Requests\ResponderChamadoRequest;
use App\Models\AnexoChamado;
use App\Models\Categoria;
use App\Models\Chamado;
use App\Models\Departamento;
use App\Models\FinalizacaoChamado;
use App\Models\FraseRapida;
use App\Models\InteracaoChamado;
use App\Services\MensagemChamadoSanitizer;
use App\Models\MotivoFinalizacao;
use App\Models\Perfil;
use App\Models\Prioridade;
use App\Models\StatusChamado;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use App\Services\ChamadoService;
use App\Services\RelatorioAtendimentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
                    ->orWhere('solicitante_nome', 'like', $termo)->orWhere('empresa_cnpj', 'like', $termo)
                    ->orWhere('empresa_nome', 'like', $termo)
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

        $consultaInteracoes = $chamado->interacoes()->with(['usuario', 'anexos', 'edicoes'])
            ->when(! $usuario->can('verNotasInternas', $chamado), fn ($q) => $q->where('tipo', '!=', 'nota_interna'))
            ->orderBy('id');
        $mensagensNaoLidas = (clone $consultaInteracoes)->where('remetente_tipo', 'cliente')->whereNull('lida_por_equipe_em')->count();
        $interacoes = $consultaInteracoes->get();
        $chamado->interacoes()->where('remetente_tipo', 'cliente')->whereNull('lida_por_equipe_em')
            ->update(['lida_por_equipe_em' => now()]);

        return view('chamados.show', [
            'chamado' => $chamado,
            'interacoes' => $interacoes,
            'frasesRapidas' => FraseRapida::where('usuario_id', $usuario->id)->orderBy('titulo')->get(),
            'mensagensNaoLidas' => $mensagensNaoLidas,
            'finalizacaoBloqueada' => in_array(
                (int) $usuario->id,
                array_map('intval', $chamado->finalizacao_bloqueada_usuarios ?? []),
                true,
            ),
            'statusLista' => StatusChamado::where('ativo', true)->where('final', false)->orderBy('ordem')->get(),
            'prioridades' => Prioridade::where('ativo', true)->orderBy('nivel')->get(),
            'departamentos' => Departamento::where('ativo', true)->orderBy('nome')->get(),
            'agentes' => Usuario::where('ativo', true)->whereHas('perfil.permissoes', fn ($q) => $q->where('chave', 'chamados.finalizar'))->orderBy('nome')->get(),
            'autorizadores' => Usuario::where('ativo', true)->whereHas('perfil', fn ($q) => $q->whereIn('slug', ['administrador', 'supervisor']))
                ->with('perfil')->orderBy('nome')->get(),
            'motivos' => MotivoFinalizacao::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function detalhamentos(Chamado $chamado, RelatorioAtendimentoService $relatorios)
    {
        $this->authorize('ver', $chamado);
        abort_unless(auth()->user()->can('verNotasInternas', $chamado), 403);

        $chamadosCliente = $this->consultarChamadosDoCliente($chamado);
        $historico = (clone $chamadosCliente)
            ->with(['status', 'departamento', 'finalizacoes'])
            ->latest('chamados.created_at')
            ->paginate(15);

        return view('chamados.detalhamentos', [
            'chamado' => $chamado->load(['solicitante', 'departamento', 'categoria', 'subcategoria', 'agente']),
            'resumoSolicitacao' => $relatorios->resumoSolicitacao($chamado),
            'totalChamados' => (clone $chamadosCliente)->count(),
            'chamadosEsteMes' => (clone $chamadosCliente)->where('chamados.created_at', '>=', now()->startOfMonth())->count(),
            'historicoCliente' => $historico,
        ]);
    }

    public function atualizarMaisDetalhamentos(Request $r, Chamado $chamado): RedirectResponse
    {
        $this->authorize('editar', $chamado);
        $dados = $r->validate([
            'demandas_recorrentes' => ['nullable', 'string', 'max:3000'],
            'tom_comunicacao_cliente' => ['nullable', Rule::in(['calmo', 'objetivo', 'preocupado', 'frustrado', 'tenso'])],
        ]);
        $this->servico->atualizarMaisDetalhamentos($chamado, $r->user(), $dados);

        return redirect()->route('chamados.detalhamentos', $chamado)->with('sucesso', 'Mais detalhamentos atualizados.');
    }

    public function relatorio(FinalizacaoChamado $finalizacao)
    {
        $finalizacao->loadMissing(['chamado']);
        $this->authorize('ver', $finalizacao->chamado);
        abort_unless(auth()->user()->can('verNotasInternas', $finalizacao->chamado), 403);
        abort_if(! $finalizacao->relatorio, 404, 'Este atendimento não possui relatório gerado.');

        return view('chamados.relatorio', [
            'finalizacao' => $finalizacao->load(['chamado', 'motivo', 'usuario']),
            'relatorio' => $finalizacao->relatorio,
        ]);
    }

    private function consultarChamadosDoCliente(Chamado $chamado)
    {
        return Chamado::query()->where(function ($consulta) use ($chamado) {
            $temIdentidade = false;
            if ($chamado->solicitante_id) {
                $consulta->where('chamados.solicitante_id', $chamado->solicitante_id);
                $temIdentidade = true;
            }
            $email = $chamado->emailSolicitante();
            if ($email) {
                $metodo = $temIdentidade ? 'orWhere' : 'where';
                $consulta->{$metodo}('chamados.solicitante_email', $email);
                $temIdentidade = true;
            }
            if ($chamado->empresa_cnpj) {
                $metodo = $temIdentidade ? 'orWhere' : 'where';
                $consulta->{$metodo}('chamados.empresa_cnpj', $chamado->empresa_cnpj);
            }
            if (! $temIdentidade) {
                $consulta->whereRaw('1 = 0');
            }
        });
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
        $dados = $r->validate([
            'agente_id' => ['required', Rule::exists('usuarios', 'id')->where('ativo', true)],
        ]);
        $agente = Usuario::findOrFail($dados['agente_id']);
        $this->servico->atribuir($chamado, $agente, $r->user());

        return back()->with('sucesso', 'Responsável atualizado.');
    }

    public function transferir(Request $r, Chamado $chamado): RedirectResponse
    {
        $this->authorize('transferir', $chamado);
        $dados = $r->validate([
            'departamento_id' => ['required', Rule::exists('departamentos', 'id')->where('ativo', true)],
            'agente_id' => ['nullable', Rule::exists('usuarios', 'id')->where('ativo', true)],
            'autorizador_id' => [
                'required',
                Rule::exists('usuarios', 'id')->where('ativo', true)->whereIn('perfil_id', Perfil::whereIn('slug', ['administrador', 'supervisor'])->select('id')),
            ],
            'prioridade_id' => ['required', Rule::exists('prioridades', 'id')->where('ativo', true)],
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ]);
        $agente = isset($dados['agente_id']) ? Usuario::findOrFail($dados['agente_id']) : null;
        $autorizador = Usuario::with('perfil')->findOrFail($dados['autorizador_id']);
        if ($agente && ((int) $agente->departamento_id !== (int) $dados['departamento_id']
            || ! $agente->temPermissao('chamados.finalizar'))) {
            return back()->withErrors(['agente_id' => 'Selecione um agente ativo do departamento de destino.'])->withInput();
        }
        $this->servico->transferirDepartamento(
            $chamado,
            (int) $dados['departamento_id'],
            $r->user(),
            $autorizador,
            (int) $dados['prioridade_id'],
            $agente,
            $dados['motivo'],
            $dados['observacoes'] ?? null,
        );

        return back()->with('sucesso', 'Atendimento transferido. O histórico e as mensagens foram preservados.');
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
        $dados = $r->validate([
            'status_id' => ['required', Rule::exists('status_chamado', 'id')->where('ativo', true)],
            'justificativa' => ['nullable', 'string', 'min:5', 'max:500'],
        ]);
        $status = StatusChamado::findOrFail($dados['status_id']);
        if (in_array($status->slug, ['aguardando_usuario', 'pendente', 'aguardando_terceiros'], true)
            && blank($dados['justificativa'] ?? null)) {
            return back()->withErrors(['justificativa' => 'Informe o que está sendo aguardado para justificar este status.'])->withInput();
        }
        $this->servico->alterarStatus($chamado, $dados['status_id'], $r->user(), $dados['justificativa'] ?? null);

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

    public function editarMensagem(Request $request, Chamado $chamado, InteracaoChamado $interacao): RedirectResponse
    {
        $this->authorize('responder', $chamado);
        $dados = $request->validate([
            'mensagem' => ['required', 'string', 'max:10000'],
        ]);
        $this->servico->corrigirMensagem($chamado, $interacao, $request->user(), $dados['mensagem']);

        return back()->with('sucesso', 'Mensagem corrigida.');
    }

    public function finalizar(FinalizarChamadoRequest $requisicao, Chamado $chamado): RedirectResponse
    {
        $d = $requisicao->validated();
        $finalizacao = $this->servico->finalizar($chamado, $requisicao->user(), $d['motivo_id'], $d['descricao_solucao'], $d['observacoes'] ?? null);

        return redirect()->route('chamados.relatorios.show', $finalizacao)
            ->with('sucesso', 'Atendimento finalizado e relatório detalhado gerado.');
    }

    public function baixarAnexo(AnexoChamado $anexo)
    {
        $this->authorize('ver', $anexo->chamado);

        return Storage::disk('local')->download($anexo->caminho, $anexo->nome_original);
    }

    public function mensagens(Request $request, Chamado $chamado): JsonResponse
    {
        $this->authorize('ver', $chamado);
        $dados = $request->validate([
            'depois_de' => ['nullable', 'integer', 'min:0'],
            'atualizadas_depois' => ['nullable', 'date'],
        ]);
        $verificadoEm = now();
        $consulta = $chamado->interacoes()->with(['usuario', 'anexos', 'edicoes'])
            ->where(function ($query) use ($dados): void {
                $query->where('id', '>', (int) ($dados['depois_de'] ?? 0));
                if (! empty($dados['atualizadas_depois'])) {
                    $query->orWhere('updated_at', '>=', $dados['atualizadas_depois']);
                }
            });
        if (! $request->user()->can('verNotasInternas', $chamado)) {
            $consulta->where('tipo', '!=', 'nota_interna');
        }
        if ($request->user()->can('chamados.responder')) {
            $chamado->interacoes()->where('remetente_tipo', 'cliente')->whereNull('lida_por_equipe_em')
                ->update(['lida_por_equipe_em' => now()]);
        }

        return response()->json([
            'verificado_em' => $verificadoEm->toDateTimeString(),
            'mensagens' => $consulta->orderBy('id')->get()->map(fn (InteracaoChamado $interacao) => [
                'id' => $interacao->id,
                'usuario_id' => $interacao->usuario_id,
                'nome' => $interacao->remetente_nome ?: $interacao->usuario?->nome ?: 'Atendimento SISLOC',
                'mensagem' => $interacao->mensagem,
                'mensagem_html' => app(MensagemChamadoSanitizer::class)->sanitizar($interacao->mensagem),
                'editada' => $interacao->edicoes->isNotEmpty(),
                'edicao_anterior' => $interacao->edicoes->first()
                    ? app(MensagemChamadoSanitizer::class)->textoSimples($interacao->edicoes->first()->mensagem_anterior)
                    : null,
                'editavel' => $interacao->usuario_id === $request->user()->id
                    && $interacao->remetente_tipo !== 'cliente'
                    && $request->user()->can('responder', $chamado),
                'edicao_url' => $interacao->usuario_id === $request->user()->id
                    && $interacao->remetente_tipo !== 'cliente'
                    && $request->user()->can('responder', $chamado)
                        ? route('chamados.interacoes.editar', [$chamado, $interacao])
                        : null,
                'criada_em' => $interacao->created_at->format('d/m/Y H:i'),
                'interna' => $interacao->tipo === 'nota_interna',
                'cliente' => $interacao->remetente_tipo === 'cliente',
                'anexos' => $interacao->anexos->map(fn ($anexo) => [
                    'nome' => $anexo->nome_original,
                    'url' => route('anexos.baixar', $anexo),
                ]),
            ]),
        ]);
    }

    public function revogarAcessoPublico(Request $request, Chamado $chamado): RedirectResponse
    {
        $this->authorize('editar', $chamado);
        $revogados = $chamado->acessosPublicos()->whereNull('revogado_em')->update(['revogado_em' => now()]);
        app(AuditoriaService::class)->registrar(
            'chamado.acesso_publico_revogado',
            'chamado',
            $chamado->id,
            ['acessos_revogados' => $revogados],
            $request->user()->id,
        );

        return back()->with('sucesso', $revogados ? 'Links públicos revogados.' : 'Não há links públicos ativos para revogar.');
    }

    public function reabrir(Request $request, Chamado $chamado): RedirectResponse
    {
        $this->authorize('ver', $chamado);
        $this->servico->reabrir($chamado, $request->user());

        return back()->with('sucesso', "Chamado {$chamado->numero} reaberto.");
    }
}
