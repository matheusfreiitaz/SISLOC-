<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampoFormularioPublico;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FormularioPublicoController extends Controller
{
    private const TIPOS = ['texto', 'texto_longo', 'email', 'numero', 'data', 'selecao', 'selecao_multipla', 'anexo', 'identificacao'];

    public function __construct(private AuditoriaService $auditoria) {}

    public function index(): View
    {
        return view('admin.formulario-publico.index', [
            'campos' => CampoFormularioPublico::orderBy('ordem')->orderBy('id')->get(),
            'tipos' => self::TIPOS,
        ]);
    }

    public function atualizar(Request $request, CampoFormularioPublico $campo): RedirectResponse
    {
        $dados = $request->validate([
            'rotulo' => ['required', 'string', 'max:120'],
            'descricao' => ['nullable', 'string', 'max:500'],
            'tipo' => ['required', Rule::in(self::TIPOS)],
            'obrigatorio' => ['required', 'boolean'],
            'ativo' => ['required', 'boolean'],
            'ordem' => ['required', 'integer', 'min:0', 'max:65535'],
            'tamanho_maximo' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'opcoes_texto' => ['nullable', 'string', 'max:4000'],
        ]);

        if ($campo->sistema) {
            $dados['tipo'] = $campo->tipo;
        }
        $opcoes = $this->opcoes($dados['opcoes_texto'] ?? '');
        if (in_array($dados['tipo'], ['selecao', 'selecao_multipla'], true) && ! $campo->sistema && ! count($opcoes)) {
            return back()->withErrors(['opcoes_texto' => 'Informe ao menos uma opção para este campo.'])->withInput();
        }
        if ($dados['tipo'] === 'anexo' && ! $this->opcoesAnexoValidas($opcoes)) {
            return back()->withErrors(['opcoes_texto' => 'Escolha extensões permitidas: jpg, jpeg, png, gif, webp, pdf, doc, docx, xls, xlsx, txt ou csv.'])->withInput();
        }

        $campo->update([
            'rotulo' => $dados['rotulo'],
            'descricao' => $dados['descricao'] ?? null,
            'tipo' => $dados['tipo'],
            'obrigatorio' => $dados['obrigatorio'],
            'ativo' => $dados['ativo'],
            'ordem' => $dados['ordem'],
            'tamanho_maximo' => $dados['tamanho_maximo'] ?? null,
            'opcoes' => $dados['tipo'] === 'anexo' ? $this->normalizarExtensoes($opcoes) : $opcoes,
        ]);
        $this->auditoria->registrar('formulario_publico.campo_atualizado', 'campo_formulario_publico', $campo->id);

        return back()->with('sucesso', 'Campo atualizado.');
    }

    public function criar(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'rotulo' => ['required', 'string', 'max:120'],
            'descricao' => ['nullable', 'string', 'max:500'],
            'tipo' => ['required', Rule::in(self::TIPOS)],
            'obrigatorio' => ['required', 'boolean'],
            'ordem' => ['required', 'integer', 'min:0', 'max:65535'],
            'tamanho_maximo' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'opcoes_texto' => ['nullable', 'string', 'max:4000'],
        ]);

        $opcoes = $this->opcoes($dados['opcoes_texto'] ?? '');
        if (in_array($dados['tipo'], ['selecao', 'selecao_multipla'], true) && ! count($opcoes)) {
            return back()->withErrors(['opcoes_texto' => 'Informe ao menos uma opção para este campo.'])->withInput();
        }
        if ($dados['tipo'] === 'anexo' && $opcoes && ! $this->opcoesAnexoValidas($opcoes)) {
            return back()->withErrors(['opcoes_texto' => 'Escolha extensões permitidas: jpg, jpeg, png, gif, webp, pdf, doc, docx, xls, xlsx, txt ou csv.'])->withInput();
        }

        $base = 'extra_'.Str::slug($dados['rotulo'], '_');
        $chave = mb_substr($base, 0, 60);
        for ($sufixo = 2; CampoFormularioPublico::where('chave', $chave)->exists(); $sufixo++) {
            $chave = mb_substr($base, 0, 55).'_'.$sufixo;
        }

        $campo = CampoFormularioPublico::create([
            'chave' => $chave,
            'rotulo' => $dados['rotulo'],
            'descricao' => $dados['descricao'] ?? null,
            'tipo' => $dados['tipo'],
            'obrigatorio' => $dados['obrigatorio'],
            'ativo' => true,
            'ordem' => $dados['ordem'],
            'tamanho_maximo' => $dados['tamanho_maximo'] ?? null,
            'opcoes' => $dados['tipo'] === 'anexo' ? $this->normalizarExtensoes($opcoes) : $opcoes,
            'sistema' => false,
        ]);
        $this->auditoria->registrar('formulario_publico.campo_criado', 'campo_formulario_publico', $campo->id, ['chave' => $chave]);

        return back()->with('sucesso', 'Campo personalizado criado.');
    }

    private function opcoes(string $texto): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $texto) ?: [])
            ->map(fn (string $opcao) => trim($opcao))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizarExtensoes(array $opcoes): array
    {
        return array_map(fn (string $extensao) => strtolower(ltrim($extensao, '.')), $opcoes);
    }

    private function opcoesAnexoValidas(array $opcoes): bool
    {
        $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'];

        return count($opcoes) > 0 && count(array_diff($this->normalizarExtensoes($opcoes), $permitidas)) === 0;
    }
}
