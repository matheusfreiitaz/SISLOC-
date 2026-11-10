<?php

namespace App\Http\Controllers;

use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ContaController extends Controller
{
    public function index()
    {
        return view('conta.index', ['usuario' => auth()->user()]);
    }

    public function tema(Request $r): JsonResponse
    {
        $dados = $r->validate(['tema' => ['required', 'in:claro,escuro']]);
        $r->user()->update(['tema' => $dados['tema']]);

        return response()->json(['tema' => $dados['tema']]);
    }

    public function atualizarPerfil(Request $r): RedirectResponse
    {
        $dados = $r->validate([
            'nome' => ['required', 'string', 'max:150'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ], ['foto.image' => 'A foto deve ser uma imagem válida.', 'foto.max' => 'A foto pode ter no máximo 2 MB.']);

        $usuario = $r->user();
        $usuario->nome = $dados['nome'];
        if ($r->hasFile('foto')) {
            if ($usuario->foto_perfil) {
                Storage::disk('public')->delete($usuario->foto_perfil);
            }
            $usuario->foto_perfil = $r->file('foto')->store('fotos', 'public');
        }
        $usuario->save();

        return back()->with('sucesso', 'Perfil atualizado.');
    }

    public function alterarSenha(Request $r, AuditoriaService $auditoria): RedirectResponse
    {
        $dados = $r->validate([
            'senha_atual' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], ['senha_atual.current_password' => 'A senha atual está incorreta.']);

        $r->user()->update(['password' => $dados['password'], 'trocar_senha' => false]);
        Auth::logoutOtherDevices($dados['password']);
        $auditoria->registrar('autenticacao.senha_alterada', 'usuario', $r->user()->id);

        return back()->with('sucesso', 'Senha alterada. Outras sessões foram encerradas.');
    }
}
