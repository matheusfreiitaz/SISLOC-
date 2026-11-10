<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResponderChamadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('responder', $this->route('chamado'));
    }

    public function rules(): array
    {
        return [
            'mensagem' => ['required', 'string', 'max:10000'],
            'tipo' => ['required', 'in:resposta,nota_interna'],
            'anexos' => ['nullable', 'array', 'max:5'],
            'anexos.*' => ['file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,txt,doc,docx,xls,xlsx,csv,zip'],
        ];
    }

    public function messages(): array
    {
        return ['mensagem.required' => 'Escreva a mensagem antes de enviar.'];
    }
}
