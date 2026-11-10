<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('usuarios.gerenciar');
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'nome' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('usuarios', 'email')->ignore($usuario?->id)],
            'password' => [$usuario ? 'nullable' : 'required', Password::min(8)->letters()->numbers()],
            'perfil_id' => ['required', 'exists:perfis,id'],
            'departamento_id' => ['nullable', 'exists:departamentos,id'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do usuário.',
            'email.unique' => 'Já existe um usuário com este e-mail.',
            'password.required' => 'Informe uma senha inicial.',
        ];
    }
}
