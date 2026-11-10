@props(['nome', 'rotulo', 'tipo' => 'text', 'valor' => null])
<div>
    <label class="rotulo" for="{{ $nome }}">{{ $rotulo }}</label>
    <input class="campo" id="{{ $nome }}" name="{{ $nome }}" type="{{ $tipo }}"
           @if ($tipo !== 'password') value="{{ old($nome, $valor) }}" @endif {{ $attributes }}>
    @error($nome)<p class="erro">{{ $message }}</p>@enderror
</div>
