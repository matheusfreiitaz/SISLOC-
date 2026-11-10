<?php

return [
    'accepted' => 'O campo :attribute deve ser aceito.',
    'array' => 'O campo :attribute deve ser uma lista.',
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'confirmed' => 'A confirmação de :attribute não confere.',
    'current_password' => 'A senha informada está incorreta.',
    'date' => 'O campo :attribute não é uma data válida.',
    'email' => 'Informe um e-mail válido.',
    'exists' => 'O valor selecionado em :attribute é inválido.',
    'file' => 'O campo :attribute deve ser um arquivo.',
    'image' => 'O campo :attribute deve ser uma imagem.',
    'in' => 'O valor selecionado em :attribute é inválido.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'mimes' => 'O campo :attribute deve ser um arquivo do tipo: :values.',
    'not_in' => 'O valor selecionado em :attribute é inválido.',
    'regex' => 'O formato de :attribute é inválido.',
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'O valor informado em :attribute já está em uso.',
    'max' => [
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
        'array' => 'O campo :attribute não pode ter mais de :max itens.',
        'file' => 'O arquivo :attribute não pode ser maior que :max KB.',
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
    ],
    'min' => [
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
    ],
    'password' => [
        'letters' => 'A senha deve conter pelo menos uma letra.',
        'numbers' => 'A senha deve conter pelo menos um número.',
        'min' => 'A senha deve ter pelo menos :min caracteres.',
    ],
    'attributes' => [
        'password' => 'senha', 'email' => 'e-mail', 'nome' => 'nome', 'assunto' => 'assunto', 'descricao' => 'descrição',
        'mensagem' => 'mensagem', 'prioridade_id' => 'prioridade', 'departamento_id' => 'departamento', 'perfil_id' => 'perfil',
    ],
];
