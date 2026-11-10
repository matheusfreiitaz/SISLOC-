<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinalizarChamadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('finalizar', $this->route('chamado'));
    }

    public function rules(): array
    {
        return [
            'motivo_id' => ['required', Rule::exists('motivos_finalizacao', 'id')->where('ativo', true)],
            'descricao_solucao' => ['required', 'string', 'max:5000'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo_id.required' => 'Selecione o motivo da finalização.',
            'motivo_id.exists' => 'O motivo selecionado não está disponível.',
            'descricao_solucao.required' => 'Descreva a solução aplicada.',
        ];
    }
}
