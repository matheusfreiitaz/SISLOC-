@extends('layouts.app')
@section('titulo', $def['titulo'])
@section('conteudo')
<div class="grid gap-6 lg:grid-cols-3">
    <form method="POST" class="cartao space-y-3 lg:col-span-1 self-start"
          action="{{ $registro ? route('admin.cadastros.update', [$tipo, $registro->id]) : route('admin.cadastros.store', $tipo) }}">
        @csrf @if ($registro) @method('PUT') @endif
        <h2 class="font-semibold">{{ $registro ? 'Editar registro' : 'Novo registro' }}</h2>
        @foreach ($def['campos'] as [$nome, $rotulo, $tipoCampo])
            @if ($tipoCampo === 'bool')
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="{{ $nome }}" value="1" @checked(old($nome, $registro?->{$nome} ?? ($nome === 'ativo' ? true : false)))> {{ $rotulo }}</label>
            @elseif (str_starts_with($tipoCampo, 'select:'))
                <div><label class="rotulo">{{ $rotulo }}</label>
                    <select name="{{ $nome }}" class="campo"><option value="">—</option>
                        @foreach ($opcoes[substr($tipoCampo, 7)] as $id => $texto)<option value="{{ $id }}" @selected(old($nome, $registro?->{$nome}) == $id)>{{ $texto }}</option>@endforeach</select>
                    @error($nome)<p class="erro">{{ $message }}</p>@enderror</div>
            @else
                <x-campo :nome="$nome" :rotulo="$rotulo" :tipo="$tipoCampo" :valor="$registro?->{$nome}" />
            @endif
        @endforeach
        <div class="flex justify-end gap-2">
            @if ($registro)<a href="{{ route('admin.cadastros.index', $tipo) }}" class="btn-neutro">Cancelar</a>@endif
            <button class="btn-primario">Salvar</button>
        </div>
    </form>
    <div class="cartao lg:col-span-2"><div class="overflow-x-auto"><table class="tabela">
        <thead><tr>@foreach ($def['campos'] as [, $rotulo])<th>{{ \Illuminate\Support\Str::before($rotulo, ' (') }}</th>@endforeach<th></th></tr></thead>
        <tbody>@foreach ($registros as $r)
            <tr>@foreach ($def['campos'] as [$nome, , $tipoCampo])
                <td>@if ($tipoCampo === 'bool'){{ $r->{$nome} ? 'Sim' : 'Não' }}
                    @elseif (str_starts_with($tipoCampo, 'select:')){{ $opcoes[substr($tipoCampo, 7)][$r->{$nome}] ?? '—' }}
                    @else {{ $r->{$nome} }} @endif</td>
            @endforeach
            <td><a class="text-orange-600 hover:underline" href="{{ route('admin.cadastros.edit', [$tipo, $r->id]) }}">Editar</a></td></tr>
        @endforeach</tbody></table></div></div>
</div>
@endsection
