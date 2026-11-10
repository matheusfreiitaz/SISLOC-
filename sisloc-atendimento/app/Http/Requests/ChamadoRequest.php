<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChamadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('chamados.criar');
    }

    public function rules(): array
    {
        return [
            'assunto' => ['required', 'string', 'max:200'],
            'descricao' => ['required', 'string', 'max:10000'],
            'departamento_id' => ['required', 'exists:departamentos,id'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'subcategoria_id' => ['nullable', 'exists:categorias,id'],
            'prioridade_id' => ['required', 'exists:prioridades,id'],
            'solicitante_id' => ['nullable', 'exists:usuarios,id'],
            'anexos' => ['nullable', 'array', 'max:5'],
            'anexos.*' => ['file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,txt,doc,docx,xls,xlsx,csv,zip'],
        ];
    }

    public function messages(): array
    {
        return [
            'assunto.required' => 'Informe o assunto do chamado.',
            'descricao.required' => 'Descreva o problema ou a solicitação.',
            'departamento_id.required' => 'Selecione o departamento.',
            'prioridade_id.required' => 'Selecione a prioridade.',
            'anexos.*.max' => 'Cada anexo pode ter no máximo 10 MB.',
            'anexos.*.mimes' => 'Tipo de arquivo não permitido.',
        ];
    }
}
