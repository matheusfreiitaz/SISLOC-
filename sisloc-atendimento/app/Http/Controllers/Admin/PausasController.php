<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PausaColaboradorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PausasController extends Controller
{
    public function atualizar(Request $request, PausaColaboradorService $pausas): RedirectResponse
    {
        $dados = $request->validate([
            'limite_simultaneas' => ['required', 'integer', 'min:1', 'max:200'],
            'sem_limite' => ['nullable', 'boolean'],
            'duracao_minutos' => ['required_without:sem_limite', 'nullable', 'integer', 'min:1', 'max:240'],
        ]);
        $duracaoMinutos = ! empty($dados['sem_limite']) ? null : (int) $dados['duracao_minutos'];
        $pausas->salvarConfiguracoes(
            (int) $dados['limite_simultaneas'],
            $duracaoMinutos,
            $request->user(),
        );

        return back()->with('sucesso', $duracaoMinutos === null
            ? 'Limite simultâneo atualizado. As pausas estão sem limite de tempo.'
            : 'Limite simultâneo e duração das pausas atualizados.');
    }
}
