<?php

namespace App\Http\Controllers;

use App\Services\PausaColaboradorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PausaColaboradorController extends Controller
{
    public function index(PausaColaboradorService $pausas): View
    {
        $configuracoes = $pausas->configuracoes();
        $usuario = request()->user();
        $visaoGerencial = $usuario->ehAdministrador()
            || in_array($usuario->perfil?->slug, ['gestor', 'gerente', 'supervisor'], true)
            || $usuario->temPermissao('cadastros.gerenciar');
        $visaoAdministrativa = $usuario->ehAdministrador() || $usuario->temPermissao('cadastros.gerenciar');

        return view('pausas.index', [
            'configuracoes' => $configuracoes,
            'totalEmPausa' => $pausas->totalEmPausa(),
            'pausaAtiva' => $pausas->pausaAtiva((int) $usuario->id),
            'proximas' => $pausas->proximasAoRetorno($visaoAdministrativa ? null : 3),
            'motivosPausa' => $pausas->motivos(),
            'colaboradores' => $visaoAdministrativa ? $pausas->colaboradores() : collect(),
            'visaoGerencial' => $visaoGerencial,
            'visaoAdministrativa' => $visaoAdministrativa,
            'historico' => $pausas->historico((int) $usuario->id, $visaoAdministrativa),
        ]);
    }

    public function iniciar(Request $request, PausaColaboradorService $pausas): RedirectResponse
    {
        $dados = $request->validate([
            'motivo' => ['required', 'string', Rule::exists('motivos_pausa', 'nome')],
        ]);
        $resultado = $pausas->iniciar($request->user(), $dados['motivo']);
        if (! $resultado['iniciada']) {
            $proximo = collect($resultado['proximas'])->first();
            if ($proximo?->prevista_ate) {
                $retorno = \Illuminate\Support\Carbon::parse($proximo->prevista_ate);
                $mensagem = "O limite de colaboradores em pausa foi atingido. {$proximo->nome} tem o retorno previsto mais próximo, às {$retorno->format('H:i')}".
                    ($retorno->isPast() ? ' (horário previsto já ultrapassado).' : '.');
            } elseif ($proximo) {
                $mensagem = "O limite de colaboradores em pausa foi atingido. {$proximo->nome} está em pausa sem limite de tempo; não há previsão de retorno.";
            } else {
                $mensagem = 'O limite de colaboradores em pausa foi atingido. Não há retorno previsto para liberar uma vaga.';
            }

            return back()->withErrors(['pausa' => $mensagem]);
        }

        return back()->with('sucesso', $resultado['ja_estava_em_pausa']
            ? 'Você já está registrado em pausa.'
            : 'Pausa iniciada. O cronômetro fica visível apenas para você e para a gestão.');
    }

    public function forcar(Request $request, PausaColaboradorService $pausas): RedirectResponse
    {
        $dados = $request->validate([
            'usuario_id' => ['required', 'integer', Rule::exists('usuarios', 'id')],
            'motivo' => ['required', 'string', Rule::exists('motivos_pausa', 'nome')],
        ]);
        $colaborador = $pausas->colaboradorDisponivel((int) $dados['usuario_id']);
        if (! $colaborador) {
            return back()->withErrors(['usuario_id' => 'Selecione um colaborador ativo da equipe.'])->withInput();
        }

        $resultado = $pausas->forcar($colaborador, $dados['motivo'], $request->user());
        if ($resultado['ja_estava_em_pausa']) {
            return back()->withErrors(['pausa' => 'Esse colaborador já está em pausa.'])->withInput();
        }

        return back()->with('sucesso', "Pausa de {$colaborador->nome} iniciada pelo administrador.");
    }

    public function criarMotivo(Request $request, PausaColaboradorService $pausas): RedirectResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:80', Rule::unique('motivos_pausa', 'nome')],
        ]);
        $pausas->adicionarMotivo(trim($dados['nome']), $request->user());

        return back()->with('sucesso', 'Nova categoria de pausa criada.');
    }

    public function retornar(Request $request, PausaColaboradorService $pausas): RedirectResponse
    {
        if (! $pausas->retornar($request->user())) {
            return back()->withErrors(['pausa' => 'Você não possui uma pausa ativa para encerrar.']);
        }

        return back()->with('sucesso', 'Retorno da pausa registrado.');
    }

    public function encerrarComoAdministrador(Request $request, int $pausa, PausaColaboradorService $pausas): RedirectResponse
    {
        $colaborador = $pausas->encerrarComoAdministrador($pausa, $request->user());
        if (! $colaborador) {
            return back()->withErrors(['pausa' => 'Essa pausa já foi encerrada ou não está mais ativa.']);
        }

        return back()->with('sucesso', "Pausa de {$colaborador} encerrada pela administração.");
    }
}
