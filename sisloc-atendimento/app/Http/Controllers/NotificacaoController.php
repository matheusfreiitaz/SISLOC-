<?php

namespace App\Http\Controllers;

use App\Models\Notificacao;
use Illuminate\Http\RedirectResponse;

class NotificacaoController extends Controller
{
    public function index()
    {
        return view('notificacoes.index', [
            'notificacoes' => Notificacao::where('usuario_id', auth()->id())->latest()->paginate(20),
        ]);
    }

    public function marcarLida(Notificacao $notificacao): RedirectResponse
    {
        abort_unless($notificacao->usuario_id === auth()->id(), 403);
        $notificacao->update(['lida_em' => now()]);

        return $notificacao->url ? redirect($notificacao->url) : back();
    }

    public function marcarTodasLidas(): RedirectResponse
    {
        Notificacao::where('usuario_id', auth()->id())->whereNull('lida_em')->update(['lida_em' => now()]);

        return back()->with('sucesso', 'Notificações marcadas como lidas.');
    }
}
