<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Departamento;
use App\Models\MotivoFinalizacao;
use App\Models\Prioridade;
use App\Models\StatusChamado;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Cadastros administrativos simples (departamentos, categorias, prioridades, status, motivos de finalização).
 * Cada tipo declara seus campos; a validação e as telas são geradas a partir dessa definição.
 */
class CadastroController extends Controller
{
    public function __construct(private AuditoriaService $auditoria) {}

    private function definicoes(): array
    {
        return [
            'departamentos' => ['titulo' => 'Departamentos', 'modelo' => Departamento::class, 'permissao' => 'cadastros.gerenciar', 'campos' => [
                ['nome', 'Nome', 'text', 'required|string|max:100'],
                ['descricao', 'Descrição', 'text', 'nullable|string|max:255'],
                ['ativo', 'Ativo', 'bool', 'boolean'],
            ]],
            'categorias' => ['titulo' => 'Categorias e subcategorias', 'modelo' => Categoria::class, 'permissao' => 'cadastros.gerenciar', 'campos' => [
                ['nome', 'Nome', 'text', 'required|string|max:100'],
                ['categoria_pai_id', 'Categoria pai (vazio = categoria principal)', 'select:categorias_pai', 'nullable|exists:categorias,id'],
                ['departamento_id', 'Departamento', 'select:departamentos', 'nullable|exists:departamentos,id'],
                ['ativo', 'Ativo', 'bool', 'boolean'],
            ]],
            'prioridades' => ['titulo' => 'Prioridades', 'modelo' => Prioridade::class, 'permissao' => 'cadastros.gerenciar', 'campos' => [
                ['nome', 'Nome', 'text', 'required|string|max:50'],
                ['cor', 'Cor', 'palette', 'required'],
                ['prazo_horas', 'Prazo de atendimento (horas)', 'number', 'required|integer|min:1|max:8760'],
                ['nivel', 'Nível (1 = menor)', 'number', 'required|integer|min:1|max:10'],
                ['ativo', 'Ativa', 'bool', 'boolean'],
            ]],
            'status' => ['titulo' => 'Status de chamados', 'modelo' => StatusChamado::class, 'permissao' => 'cadastros.gerenciar', 'campos' => [
                ['nome', 'Nome', 'text', 'required|string|max:50'],
                ['cor', 'Cor', 'palette', 'required'],
                ['ordem', 'Ordem', 'number', 'required|integer|min:0|max:100'],
                ['final', 'Status final (encerra o chamado)', 'bool', 'boolean'],
                ['ativo', 'Ativo', 'bool', 'boolean'],
            ]],
            'finalizacoes' => ['titulo' => 'Motivos de finalização', 'modelo' => MotivoFinalizacao::class, 'permissao' => 'finalizacoes.gerenciar', 'campos' => [
                ['nome', 'Motivo', 'text', 'required|string|max:120'],
                ['ativo', 'Ativo', 'bool', 'boolean'],
            ]],
        ];
    }

    private function definicao(string $tipo): array
    {
        $d = $this->definicoes()[$tipo] ?? abort(404);
        abort_unless(auth()->user()->can($d['permissao']), 403);

        return $d;
    }

    public function index(string $tipo)
    {
        $d = $this->definicao($tipo);

        return view('admin.cadastros.index', [
            'tipo' => $tipo, 'def' => $d, 'registro' => null,
            'registros' => $d['modelo']::orderBy('id')->get(),
            'opcoes' => $this->opcoes(),
            'todas' => $this->definicoes(),
        ]);
    }

    public function edit(string $tipo, int $id)
    {
        $d = $this->definicao($tipo);

        return view('admin.cadastros.index', [
            'tipo' => $tipo, 'def' => $d, 'registro' => $d['modelo']::findOrFail($id),
            'registros' => $d['modelo']::orderBy('id')->get(),
            'opcoes' => $this->opcoes(),
            'todas' => $this->definicoes(),
        ]);
    }

    public function store(Request $r, string $tipo): RedirectResponse
    {
        $d = $this->definicao($tipo);
        $dados = $this->validar($r, $d);
        $registro = $d['modelo']::create($dados);
        $this->auditoria->registrar("cadastro.{$tipo}.criado", $tipo, $registro->id, $dados);

        return redirect()->route('admin.cadastros.index', $tipo)->with('sucesso', 'Registro criado.');
    }

    public function update(Request $r, string $tipo, int $id): RedirectResponse
    {
        $d = $this->definicao($tipo);
        $registro = $d['modelo']::findOrFail($id);
        $dados = $this->validar($r, $d, $registro);
        $registro->update($dados);
        $this->auditoria->registrar("cadastro.{$tipo}.atualizado", $tipo, $registro->id, $dados);

        return redirect()->route('admin.cadastros.index', $tipo)->with('sucesso', 'Registro atualizado.');
    }

    private function validar(Request $r, array $d, $registro = null): array
    {
        $regras = [];
        foreach ($d['campos'] as [$nome, , $tipoCampo, $regra]) {
            $regras[$nome] = match ($tipoCampo) {
                'palette' => ['required', Rule::in(array_keys($this->coresDisponiveis()))],
                default => str_contains($regra, 'regex:') ? [$regra] : explode('|', $regra),
            };
            if ($nome === 'nome' && in_array($d['modelo'], [Departamento::class, Prioridade::class, StatusChamado::class, MotivoFinalizacao::class], true)) {
                $regras[$nome][] = 'unique:'.(new $d['modelo'])->getTable().',nome'.($registro ? ",{$registro->id}" : '');
            }
        }
        if ($d['modelo'] === Categoria::class && $registro) {
            $regras['categoria_pai_id'][] = "not_in:{$registro->id}";
        }

        $dados = $r->validate($regras, ['nome.required' => 'Informe o nome.', 'nome.unique' => 'Já existe um registro com este nome.']);
        foreach ($d['campos'] as [$nome, , $tipoCampo]) {
            if ($tipoCampo === 'bool') {
                $dados[$nome] = $r->boolean($nome);
            }
        }
        if ($d['modelo'] === StatusChamado::class) {
            $dados['slug'] = $registro?->slug ?? Str::slug($dados['nome'], '_');
        }

        return $dados;
    }

    private function opcoes(): array
    {
        return [
            'departamentos' => Departamento::orderBy('nome')->pluck('nome', 'id')->all(),
            'categorias_pai' => Categoria::whereNull('categoria_pai_id')->orderBy('nome')->pluck('nome', 'id')->all(),
            'cores' => $this->coresDisponiveis(),
        ];
    }

    private function coresDisponiveis(): array
    {
        return [
            '#3b82f6' => 'Azul',
            '#f97316' => 'Laranja',
            '#eab308' => 'Amarelo',
            '#a855f7' => 'Roxo',
            '#64748b' => 'Cinza',
            '#22c55e' => 'Verde',
            '#475569' => 'Grafite',
            '#ef4444' => 'Vermelho',
            '#06b6d4' => 'Ciano',
            '#14b8a6' => 'Turquesa',
            '#ec4899' => 'Rosa',
            '#6366f1' => 'Índigo',
        ];
    }
}
