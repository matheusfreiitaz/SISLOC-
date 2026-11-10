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
            @elseif ($tipoCampo === 'palette')
                <fieldset>
                    <legend class="rotulo">{{ $rotulo }}</legend>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach ($opcoes['cores'] as $hex => $corNome)
                            <label class="cursor-pointer">
                                <input class="peer sr-only" type="radio" name="{{ $nome }}" value="{{ $hex }}" @checked(old($nome, $registro?->{$nome}) === $hex)>
                                <span class="flex items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-2 text-xs text-slate-700 transition peer-checked:border-orange-500 peer-checked:bg-orange-50 peer-checked:ring-2 peer-checked:ring-orange-500/20 dark:border-slate-700 dark:text-slate-200 dark:peer-checked:bg-orange-500/10">
                                    <i class="h-4 w-4 shrink-0 rounded-full border border-black/10" style="background-color: {{ $hex }}"></i>
                                    <span>{{ $corNome }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error($nome)<p class="erro">{{ $message }}</p>@enderror
                </fieldset>
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
                    @elseif ($tipoCampo === 'palette')<span class="inline-flex items-center gap-2"><i class="h-4 w-4 rounded-full border border-black/10" style="background-color: {{ $r->{$nome} }}"></i>{{ $r->{$nome} }}</span>
                    @else {{ $r->{$nome} }} @endif</td>
            @endforeach
            <td><a class="text-orange-600 hover:underline" href="{{ route('admin.cadastros.edit', [$tipo, $r->id]) }}">Editar</a></td></tr>
        @endforeach</tbody></table></div></div>
</div>
@endsection
