<?php

namespace App\Http\Controllers;

use App\Models\Chamado;
use App\Models\FraseRapida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FraseRapidaController extends Controller
{
    public function store(Request $request, Chamado $chamado): RedirectResponse
    {
        $this->authorize('responder', $chamado);
        $dados = $this->validar($request);
        FraseRapida::create([
            'usuario_id' => $request->user()->id,
            ...$dados,
        ]);

        return back()->with('sucesso', 'Frase rápida salva para sua conta.');
    }

    public function update(Request $request, Chamado $chamado, FraseRapida $frase): RedirectResponse
    {
        $this->authorize('responder', $chamado);
        abort_unless($frase->usuario_id === $request->user()->id, 404);
        $dados = $this->validar($request, $frase);
        $frase->update($dados);

        return back()->with('sucesso', 'Frase rápida atualizada.');
    }

    public function destroy(Request $request, Chamado $chamado, FraseRapida $frase): RedirectResponse
    {
        $this->authorize('responder', $chamado);
        abort_unless($frase->usuario_id === $request->user()->id, 404);
        $frase->delete();

        return back()->with('sucesso', 'Frase rápida excluída.');
    }

    private function validar(Request $request, ?FraseRapida $frase = null): array
    {
        return $request->validate([
            'titulo' => [
                'required', 'string', 'max:80',
                Rule::unique('frases_rapidas', 'titulo')->where('usuario_id', $request->user()->id)->ignore($frase?->id),
            ],
            'conteudo' => ['required', 'string', 'max:3000'],
        ]);
    }
}
