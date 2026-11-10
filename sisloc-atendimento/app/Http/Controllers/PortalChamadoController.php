<?php

namespace App\Http\Controllers;

use App\Models\AcessoPublicoChamado;
use App\Models\CampoFormularioPublico;
use App\Models\Categoria;
use App\Models\Chamado;
use App\Models\Departamento;
use App\Models\HistoricoChamado;
use App\Models\InteracaoChamado;
use App\Models\Prioridade;
use App\Models\StatusChamado;
use App\Services\AuditoriaService;
use App\Services\NotificacaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PortalChamadoController extends Controller
{
    private const TIPOS_ARQUIVO = 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv';

    public function __construct(
        private AuditoriaService $auditoria,
        private NotificacaoService $notificacoes,
    ) {}

    public function criar(): View
    {
        return view('portal.chamados.criar', [
            'campos' => CampoFormularioPublico::where('ativo', true)->orderBy('ordem')->get(),
            'categorias' => Categoria::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function armazenar(Request $request): RedirectResponse
    {
        $campos = CampoFormularioPublico::where('ativo', true)->orderBy('ordem')->get();
        $dados = (array) $request->input('campos', []);
        if (array_key_exists('cnpj', $dados)) {
            $dados['cnpj'] = preg_replace('/\D+/', '', (string) $dados['cnpj']);
            $request->merge(['campos' => $dados]);
        }

        $regras = [];
        $mensagens = [];
        foreach ($campos as $campo) {
            $caminho = $campo->tipo === 'anexo' ? "arquivos.{$campo->chave}" : "campos.{$campo->chave}";
            $regras[$caminho] = $this->regrasCampo($campo);
            if ($campo->tipo === 'anexo') {
                $extensoes = $this->extensoesPermitidas($campo);
                $regras["{$caminho}.*"] = ['file', 'mimes:'.implode(',', $extensoes), 'max:'.($campo->tamanho_maximo ?: 10240)];
            }
            if ($campo->tipo === 'selecao_multipla') {
                $regras["{$caminho}.*"] = ['string', Rule::in($campo->opcoes ?? [])];
            }
            $mensagens["{$caminho}.required"] = "Preencha o campo {$campo->rotulo}.";
            $mensagens["{$caminho}.email"] = 'Informe um endereço de e-mail válido.';
        }

        $validados = Validator::make($request->all(), $regras, $mensagens)->validate();
        $valores = $validados['campos'] ?? [];
        $categoria = isset($valores['categoria']) ? Categoria::with('pai')->findOrFail($valores['categoria']) : null;
        $departamento = $categoria?->departamento;
        if (! $departamento?->ativo) {
            $departamento = $categoria?->pai?->departamento;
        }
        if (! $departamento?->ativo) {
            $departamento = Departamento::where('nome', 'Outros')->where('ativo', true)->first();
        }
        $prioridade = Prioridade::where('ativo', true)->orderBy('nivel')->first();

        if (! $departamento || ! $prioridade) {
            throw ValidationException::withMessages([
                'campos.categoria' => 'Não há departamento ou prioridade ativa para receber chamados. Contate o suporte.',
            ]);
        }

        $anexos = [];
        foreach ($campos->where('tipo', 'anexo') as $campo) {
            $arquivos = $validados['arquivos'][$campo->chave] ?? [];
            foreach (is_array($arquivos) ? $arquivos : [$arquivos] as $arquivo) {
                if ($arquivo) {
                    $anexos[] = $arquivo;
                }
            }
            if (count($anexos) > 5) {
                throw ValidationException::withMessages(['arquivos' => 'Anexe no máximo cinco arquivos por solicitação.']);
            }
        }

        $categoriaNome = $categoria?->nome ?? 'Solicitação pública';
        $cnpj = $valores['cnpj'] ?? null;
        $empresa = $valores['empresa_nome'] ?? null;
        $email = $valores['email'] ?? null;
        $nome = $valores['nome'] ?? null;
        $token = $email ? Str::random(64) : null;

        $chamado = DB::transaction(function () use ($valores, $categoria, $departamento, $prioridade, $anexos, $cnpj, $empresa, $email, $nome, $token, $categoriaNome) {
            $chamado = Chamado::create([
                'solicitante_id' => null,
                'solicitante_nome' => $nome,
                'solicitante_email' => $email,
                'empresa_cnpj' => $cnpj,
                'empresa_nome' => $empresa,
                'departamento_id' => $departamento->id,
                'categoria_id' => $categoria?->id,
                'assunto' => mb_substr($categoriaNome, 0, 200),
                'descricao' => $valores['relato'] ?? 'Solicitação pública',
                'funcionamento_esperado' => $valores['esperado'] ?? null,
                'campos_publicos' => collect($valores)->except(['cnpj', 'empresa_nome', 'email', 'nome', 'categoria', 'relato', 'esperado'])->all(),
                'prioridade_id' => $prioridade->id,
                'status_id' => StatusChamado::idPorSlug('aberto'),
                'prazo_em' => now()->addHours($prioridade->prazo_horas),
            ]);
            $chamado->update(['numero' => sprintf('%s%06d', now()->format('Y'), $chamado->id)]);

            HistoricoChamado::create([
                'chamado_id' => $chamado->id,
                'campo' => 'chamado',
                'valor_novo' => 'Aberto pelo portal público',
            ]);

            foreach ($anexos as $arquivo) {
                $caminho = $arquivo->store("anexos/{$chamado->id}", 'local');
                if (! $caminho) {
                    throw new \RuntimeException('Não foi possível armazenar um dos anexos do chamado.');
                }
                $chamado->anexos()->create([
                    'usuario_id' => null,
                    'nome_original' => $arquivo->getClientOriginalName(),
                    'caminho' => $caminho,
                    'mime' => $arquivo->getMimeType(),
                    'tamanho' => $arquivo->getSize(),
                ]);
            }

            if ($token && $email) {
                $chamado->acessosPublicos()->create([
                    'token_hash' => hash('sha256', $token),
                    'expira_em' => now()->addDays(90),
                ]);
            }

            $this->auditoria->registrar('chamado.publico_criado', 'chamado', $chamado->id, [
                'numero' => $chamado->numero,
                'categoria_id' => $categoria?->id,
            ]);
            $this->notificacoes->notificarDepartamento(
                $chamado->departamento_id,
                'chamado.novo',
                "Novo chamado {$chamado->numero}",
                $chamado->assunto,
                route('chamados.show', $chamado),
            );

            return $chamado;
        });

        $urlAcompanhamento = $token ? route('portal.chamados.show', $token) : null;
        if ($urlAcompanhamento && $email) {
            Mail::raw(
                "Olá, {$nome}.\n\nSeu chamado {$chamado->numero} foi registrado com sucesso.\nAcompanhe e responda com segurança neste link individual:\n{$urlAcompanhamento}\n\nEste link expira em 90 dias.",
                fn ($mensagem) => $mensagem->to($email)->subject("Acesso ao chamado {$chamado->numero}")
            );
        }

        return redirect()->route('portal.chamados.confirmacao')
            ->with('protocolo', $chamado->numero)
            ->with('acompanhamento', $urlAcompanhamento);
    }

    public function confirmacao(): View
    {
        abort_unless(session()->has('protocolo'), 404);

        return view('portal.chamados.confirmacao');
    }

    public function acompanhar(string $token): View
    {
        $acesso = $this->acessoValido($token);
        $chamado = $acesso->chamado->load(['status', 'categoria', 'departamento', 'anexos.interacao']);
        $mensagensNaoLidas = $chamado->interacoes()->where('tipo', '!=', 'nota_interna')
            ->where('remetente_tipo', '!=', 'cliente')->whereNull('lida_por_cliente_em')->count();
        $historicoRecente = $chamado->solicitante_email && $chamado->empresa_cnpj
            ? Chamado::query()
                ->where('solicitante_email', $chamado->solicitante_email)
                ->where('empresa_cnpj', $chamado->empresa_cnpj)
                ->where('created_at', '>=', now()->subDays(30))
                ->with(['status', 'categoria'])
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
            : collect();
        $acesso->update(['ultimo_acesso_em' => now()]);
        $this->marcarMensagensLidasPeloCliente($chamado);

        return view('portal.chamados.acompanhar', [
            'chamado' => $chamado,
            'token' => $token,
            'historicoRecente' => $historicoRecente,
            'interacoes' => $chamado->interacoes()->where('tipo', '!=', 'nota_interna')->with(['anexos', 'edicoes'])->orderBy('id')->get(),
            'mensagensNaoLidas' => $mensagensNaoLidas,
        ]);
    }

    public function mensagens(Request $request, string $token): JsonResponse
    {
        $acesso = $this->acessoValido($token);
        $chamado = $acesso->chamado;
        $dados = $request->validate([
            'depois_de' => ['nullable', 'integer', 'min:0'],
            'atualizadas_depois' => ['nullable', 'date'],
        ]);
        $depoisDe = (int) ($dados['depois_de'] ?? 0);
        $verificadoEm = now();
        $mensagens = $chamado->interacoes()->where('tipo', '!=', 'nota_interna')
            ->where(function ($query) use ($depoisDe, $dados): void {
                $query->where('id', '>', $depoisDe);
                if (! empty($dados['atualizadas_depois'])) {
                    $query->orWhere('updated_at', '>=', $dados['atualizadas_depois']);
                }
            })->with(['anexos', 'edicoes'])->orderBy('id')->get();

        $this->marcarMensagensLidasPeloCliente($chamado);
        $acesso->update(['ultimo_acesso_em' => now()]);

        return response()->json([
            'verificado_em' => $verificadoEm->toDateTimeString(),
            'finalizado' => $chamado->estaFinalizado(),
            'mensagens' => $mensagens->map(fn (InteracaoChamado $mensagem) => [
                'id' => $mensagem->id,
                'nome' => $mensagem->remetente_nome ?: $mensagem->usuario?->nome ?: 'Atendimento SISLOC',
                'mensagem' => $mensagem->mensagem,
                'mensagem_html' => app(\App\Services\MensagemChamadoSanitizer::class)->sanitizar($mensagem->mensagem),
                'editada' => $mensagem->edicoes->isNotEmpty(),
                'edicao_anterior' => $mensagem->edicoes->first()
                    ? app(\App\Services\MensagemChamadoSanitizer::class)->textoSimples($mensagem->edicoes->first()->mensagem_anterior)
                    : null,
                'criada_em' => $mensagem->created_at->format('d/m/Y H:i'),
                'cliente' => $mensagem->remetente_tipo === 'cliente',
                'anexos' => $mensagem->anexos->map(fn ($anexo) => [
                    'nome' => $anexo->nome_original,
                    'url' => route('portal.chamados.anexos', [$token, $anexo]),
                ]),
            ]),
        ]);
    }

    public function responder(Request $request, string $token): RedirectResponse
    {
        $acesso = $this->acessoValido($token);
        $chamado = $acesso->chamado;
        abort_if($chamado->estaFinalizado(), 403, 'Este atendimento foi finalizado e aceita somente consulta.');

        $dados = $request->validate([
            'mensagem' => ['required', 'string', 'max:10000'],
            'anexos' => ['sometimes', 'array', 'max:5'],
            'anexos.*' => ['file', 'mimes:'.self::TIPOS_ARQUIVO, 'max:10240'],
        ]);
        $dados['mensagem'] = app(\App\Services\MensagemChamadoSanitizer::class)->sanitizar($dados['mensagem']);
        if (blank(app(\App\Services\MensagemChamadoSanitizer::class)->textoSimples($dados['mensagem']))) {
            return back()->withErrors(['mensagem' => 'Escreva uma mensagem antes de enviar.'])->withInput();
        }
        $textoPlano = app(\App\Services\MensagemChamadoSanitizer::class)->textoSimples($dados['mensagem']);

        DB::transaction(function () use ($chamado, $dados, $request, $textoPlano) {
            $interacao = $chamado->interacoes()->create([
                'usuario_id' => null,
                'tipo' => 'resposta',
                'remetente_tipo' => 'cliente',
                'remetente_nome' => $chamado->nomeSolicitante(),
                'mensagem' => $dados['mensagem'],
            ]);

            foreach ($request->file('anexos', []) as $arquivo) {
                $caminho = $arquivo->store("anexos/{$chamado->id}", 'local');
                if (! $caminho) {
                    throw new \RuntimeException('Não foi possível armazenar um dos anexos da mensagem.');
                }
                $interacao->anexos()->create([
                    'chamado_id' => $chamado->id,
                    'usuario_id' => null,
                    'nome_original' => $arquivo->getClientOriginalName(),
                    'caminho' => $caminho,
                    'mime' => $arquivo->getMimeType(),
                    'tamanho' => $arquivo->getSize(),
                ]);
            }

            if ($chamado->status->slug === 'aguardando_usuario') {
                $anterior = $chamado->status->nome;
                $chamado->status_id = StatusChamado::idPorSlug('em_atendimento');
                HistoricoChamado::create([
                    'chamado_id' => $chamado->id,
                    'campo' => 'status',
                    'valor_anterior' => $anterior,
                    'valor_novo' => 'Em atendimento',
                ]);
            }
            $chamado->touch();
            $chamado->save();
            $this->notificacoes->notificar(
                $chamado->agente_id,
                'chamado.resposta',
                "Nova mensagem do solicitante em {$chamado->numero}",
                mb_strimwidth($textoPlano, 0, 120, '…'),
                route('chamados.show', $chamado),
            );
            $this->auditoria->registrar('chamado.mensagem_publica', 'chamado', $chamado->id, ['interacao_id' => $interacao->id]);
        });

        return back()->with('sucesso', 'Mensagem enviada ao atendimento.');
    }

    public function baixarAnexo(string $token, int $anexoId)
    {
        $chamado = $this->acessoValido($token)->chamado;
        $anexo = $chamado->anexos()->whereKey($anexoId)->firstOrFail();
        if ($anexo->interacao_id) {
            abort_if($anexo->interacao()->where('tipo', 'nota_interna')->exists(), 404);
        }

        return Storage::disk('local')->download($anexo->caminho, $anexo->nome_original);
    }

    private function acessoValido(string $token): AcessoPublicoChamado
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{64}$/', $token), 404);

        return AcessoPublicoChamado::with('chamado.status')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('revogado_em')
            ->where('expira_em', '>', now())
            ->firstOrFail();
    }

    private function marcarMensagensLidasPeloCliente(Chamado $chamado): void
    {
        $chamado->interacoes()->where('remetente_tipo', '!=', 'cliente')
            ->where('tipo', '!=', 'nota_interna')->whereNull('lida_por_cliente_em')
            ->update(['lida_por_cliente_em' => now()]);
    }

    private function regrasCampo(CampoFormularioPublico $campo): array
    {
        $obrigatorio = $campo->obrigatorio ? 'required' : 'nullable';
        $maximo = $campo->tamanho_maximo ?: 1000;

        return match ($campo->tipo) {
            'email' => [$obrigatorio, 'email:rfc', 'max:254'],
            'identificacao' => [
                $obrigatorio,
                'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value !== null && $value !== '' && ! $this->cnpjValido((string) $value)) {
                        $fail('Informe um CNPJ válido.');
                    }
                },
            ],
            'texto' => [$obrigatorio, 'string', "max:{$maximo}"],
            'texto_longo' => [$obrigatorio, 'string', "max:{$maximo}"],
            'numero' => [$obrigatorio, 'numeric'],
            'data' => [$obrigatorio, 'date'],
            'selecao' => $campo->chave === 'categoria'
                ? [$obrigatorio, 'integer', Rule::exists('categorias', 'id')->where('ativo', true)]
                : [$obrigatorio, 'string', Rule::in($campo->opcoes ?? [])],
            'selecao_multipla' => [$obrigatorio, 'array', 'max:50'],
            'anexo' => [$obrigatorio, 'array', 'max:5'],
            default => [$obrigatorio, 'string', "max:{$maximo}"],
        };
    }

    private function extensoesPermitidas(CampoFormularioPublico $campo): array
    {
        $permitidas = explode(',', self::TIPOS_ARQUIVO);

        return array_values(array_intersect($campo->opcoes ?: $permitidas, $permitidas));
    }

    private function cnpjValido(string $cnpj): bool
    {
        if (! preg_match('/^\d{14}$/', $cnpj) || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        foreach ([[5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]] as $etapa => $pesos) {
            $soma = 0;
            foreach ($pesos as $indice => $peso) {
                $soma += (int) $cnpj[$indice] * $peso;
            }
            $resto = $soma % 11;
            $digito = $resto < 2 ? 0 : 11 - $resto;
            if ((int) $cnpj[count($pesos)] !== $digito) {
                return false;
            }
        }

        return true;
    }
}
