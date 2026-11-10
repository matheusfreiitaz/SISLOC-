<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as RegraSenha;
use Illuminate\Validation\ValidationException;

class AutenticacaoController extends Controller
{
    public function __construct(private AuditoriaService $auditoria) {}

    public function formularioLogin()
    {
        return view('autenticacao.login');
    }

    public function entrar(Request $requisicao): RedirectResponse
    {
        $credenciais = $requisicao->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Informe seu e-mail.',
            'password.required' => 'Informe sua senha.',
        ]);

        if (! Auth::attempt([...$credenciais, 'ativo' => true], $requisicao->boolean('lembrar'))) {
            throw ValidationException::withMessages(['email' => 'E-mail ou senha inválidos, ou conta desativada.']);
        }

        $requisicao->session()->regenerate();
        $usuario = $requisicao->user();
        $usuario->forceFill(['ultimo_acesso_em' => now()])->save();
        $this->auditoria->registrar('autenticacao.login', 'usuario', $usuario->id);

        return redirect()->intended(route('painel'));
    }

    public function sair(Request $requisicao): RedirectResponse
    {
        $this->auditoria->registrar('autenticacao.logout', 'usuario', Auth::id());
        Auth::logout();
        $requisicao->session()->invalidate();
        $requisicao->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function formularioEsqueciSenha()
    {
        return view('autenticacao.esqueci-senha');
    }

    public function enviarLinkRecuperacao(Request $requisicao): RedirectResponse
    {
        $requisicao->validate(['email' => ['required', 'email']], ['email.required' => 'Informe seu e-mail.']);
        Password::sendResetLink($requisicao->only('email'));

        // Resposta neutra: não revela se o e-mail existe.
        return back()->with('sucesso', 'Se o e-mail estiver cadastrado, enviaremos as instruções de recuperação.');
    }

    public function formularioRedefinirSenha(Request $requisicao, string $token)
    {
        return view('autenticacao.redefinir-senha', ['token' => $token, 'email' => $requisicao->query('email')]);
    }

    public function redefinirSenha(Request $requisicao): RedirectResponse
    {
        $dados = $requisicao->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', RegraSenha::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset($dados, function (Usuario $usuario, string $senha) {
            $usuario->forceFill(['password' => $senha, 'trocar_senha' => false, 'remember_token' => Str::random(60)])->save();
            $this->auditoria->registrar('autenticacao.senha_redefinida', 'usuario', $usuario->id, [], $usuario->id);
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'Link inválido ou expirado. Solicite uma nova recuperação.']);
        }

        return redirect()->route('login')->with('sucesso', 'Senha redefinida. Faça login com a nova senha.');
    }
}
