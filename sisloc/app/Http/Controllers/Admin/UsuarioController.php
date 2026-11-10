<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UsuarioRequest;
use App\Models\Departamento;
use App\Models\Perfil;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function __construct(private AuditoriaService $auditoria) {}

    public function index(Request $r)
    {
        $usuarios = Usuario::with(['perfil', 'departamento'])
            ->when($r->filled('busca'), fn ($q) => $q->where(fn ($q) => $q->where('nome', 'like', '%'.$r->string('busca').'%')
                ->orWhere('email', 'like', '%'.$r->string('busca').'%')))
            ->orderBy('nome')->paginate(15)->withQueryString();

        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        return view('admin.usuarios.form', ['usuario' => new Usuario(['ativo' => true]), ...$this->listas()]);
    }

    public function store(UsuarioRequest $requisicao): RedirectResponse
    {
        $dados = $requisicao->validated();
        $dados['ativo'] = $requisicao->boolean('ativo');
        $dados['trocar_senha'] = true; // orienta troca no primeiro acesso
        $usuario = Usuario::create($dados);
        $this->auditoria->registrar('usuario.criado', 'usuario', $usuario->id, ['email' => $usuario->email]);

        return redirect()->route('admin.usuarios.index')->with('sucesso', 'Usuário criado.');
    }

    public function edit(Usuario $usuario)
    {
        return view('admin.usuarios.form', ['usuario' => $usuario, ...$this->listas()]);
    }

    public function update(UsuarioRequest $requisicao, Usuario $usuario): RedirectResponse
    {
        $dados = $requisicao->validated();
        $dados['ativo'] = $requisicao->boolean('ativo');
        if (empty($dados['password'])) {
            unset($dados['password']);
        }
        if ($usuario->id === auth()->id() && ! $dados['ativo']) {
            return back()->withErrors(['ativo' => 'Você não pode desativar a própria conta.']);
        }
        $usuario->update($dados);
        $this->auditoria->registrar('usuario.atualizado', 'usuario', $usuario->id, ['ativo' => $usuario->ativo, 'perfil_id' => $usuario->perfil_id]);

        return redirect()->route('admin.usuarios.index')->with('sucesso', 'Usuário atualizado.');
    }

    private function listas(): array
    {
        return ['perfis' => Perfil::orderBy('nome')->get(), 'departamentos' => Departamento::orderBy('nome')->get()];
    }
}
