<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PerfilController extends Controller
{
    public function __construct(private AuditoriaService $auditoria) {}

    public function index()
    {
        return view('admin.perfis.index', ['perfis' => Perfil::withCount(['usuarios', 'permissoes'])->orderBy('nome')->get()]);
    }

    public function create()
    {
        return view('admin.perfis.form', ['perfil' => new Perfil, 'permissoes' => Permissao::orderBy('modulo')->orderBy('chave')->get()->groupBy('modulo')]);
    }

    public function store(Request $r): RedirectResponse
    {
        $dados = $this->validar($r);
        $perfil = Perfil::create(['nome' => $dados['nome'], 'slug' => Str::slug($dados['nome']), 'descricao' => $dados['descricao'] ?? null]);
        $perfil->permissoes()->sync($dados['permissoes'] ?? []);
        $this->auditoria->registrar('perfil.criado', 'perfil', $perfil->id, ['permissoes' => $dados['permissoes'] ?? []]);

        return redirect()->route('admin.perfis.index')->with('sucesso', 'Perfil criado.');
    }

    public function edit(Perfil $perfil)
    {
        return view('admin.perfis.form', [
            'perfil' => $perfil->load('permissoes'),
            'permissoes' => Permissao::orderBy('modulo')->orderBy('chave')->get()->groupBy('modulo'),
        ]);
    }

    public function update(Request $r, Perfil $perfil): RedirectResponse
    {
        $dados = $this->validar($r, $perfil);
        $perfil->update(['nome' => $dados['nome'], 'descricao' => $dados['descricao'] ?? null]);
        if ($perfil->slug !== 'administrador') { // o administrador sempre possui acesso total
            $perfil->permissoes()->sync($dados['permissoes'] ?? []);
        }
        $this->auditoria->registrar('perfil.permissoes_alteradas', 'perfil', $perfil->id, ['permissoes' => $dados['permissoes'] ?? []]);

        return redirect()->route('admin.perfis.index')->with('sucesso', 'Perfil atualizado.');
    }

    public function destroy(Perfil $perfil): RedirectResponse
    {
        if ($perfil->sistema || $perfil->usuarios()->exists()) {
            return back()->withErrors(['perfil' => 'Perfis do sistema ou com usuários vinculados não podem ser excluídos.']);
        }
        $this->auditoria->registrar('perfil.excluido', 'perfil', $perfil->id, ['nome' => $perfil->nome]);
        $perfil->delete();

        return redirect()->route('admin.perfis.index')->with('sucesso', 'Perfil excluído.');
    }

    private function validar(Request $r, ?Perfil $perfil = null): array
    {
        return $r->validate([
            'nome' => ['required', 'string', 'max:100', 'unique:perfis,nome'.($perfil ? ",{$perfil->id}" : '')],
            'descricao' => ['nullable', 'string', 'max:255'],
            'permissoes' => ['nullable', 'array'],
            'permissoes.*' => ['exists:permissoes,id'],
        ], ['nome.required' => 'Informe o nome do perfil.', 'nome.unique' => 'Já existe um perfil com este nome.']);
    }
}
