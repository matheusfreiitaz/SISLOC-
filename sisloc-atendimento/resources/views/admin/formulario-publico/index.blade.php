@extends('layouts.app')
@section('titulo', 'Configuração do formulário de atendimento')
@section('conteudo')
<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <p class="text-sm text-slate-500">Defina os campos, a obrigatoriedade e a ordem do portal público.</p>
        <p class="mt-1 text-xs text-slate-500">Somente campos ativos aparecem para clientes. Categorias são carregadas do cadastro atual; anexos permanecem em armazenamento privado.</p>
    </div>
    @foreach ($campos as $campo)
        <form method="POST" action="{{ route('admin.formulario-publico.atualizar', $campo) }}" class="cartao grid gap-4 md:grid-cols-2">
            @csrf @method('PUT')
            <div class="md:col-span-2 flex items-center justify-between">
                <h2 class="font-semibold">{{ $campo->rotulo }} <span class="ml-1 text-xs font-normal text-slate-500">{{ $campo->sistema ? 'Campo do sistema' : 'Personalizado' }} · {{ $campo->chave }}</span></h2>
                <button class="btn-neutro">Salvar campo</button>
            </div>
            <div><label class="rotulo" for="rotulo-{{ $campo->id }}">Nome do campo</label><input id="rotulo-{{ $campo->id }}" class="campo" name="rotulo" value="{{ old('rotulo', $campo->rotulo) }}" maxlength="120" required></div>
            <div><label class="rotulo" for="tipo-{{ $campo->id }}">Tipo</label>
                <select id="tipo-{{ $campo->id }}" class="campo" name="tipo" @disabled($campo->sistema)>
                    @foreach ($tipos as $tipo)<option value="{{ $tipo }}" @selected($campo->tipo === $tipo)>{{ str_replace('_', ' ', ucfirst($tipo)) }}</option>@endforeach
                </select>
                @if ($campo->sistema)<input type="hidden" name="tipo" value="{{ $campo->tipo }}">@endif
            </div>
            <div class="md:col-span-2"><label class="rotulo" for="descricao-{{ $campo->id }}">Descrição ou orientação</label><textarea id="descricao-{{ $campo->id }}" class="campo" name="descricao" rows="2" maxlength="500">{{ old('descricao', $campo->descricao) }}</textarea></div>
            <div><label class="rotulo" for="ordem-{{ $campo->id }}">Ordem de exibição</label><input id="ordem-{{ $campo->id }}" class="campo" type="number" name="ordem" min="0" max="65535" value="{{ old('ordem', $campo->ordem) }}" required></div>
            <div><label class="rotulo" for="max-{{ $campo->id }}">{{ $campo->tipo === 'anexo' ? 'Tamanho máximo (KB)' : 'Tamanho máximo (caracteres)' }}</label><input id="max-{{ $campo->id }}" class="campo" type="number" name="tamanho_maximo" min="1" max="100000" value="{{ old('tamanho_maximo', $campo->tamanho_maximo) }}"></div>
            <div class="flex flex-wrap gap-5">
                <label class="flex items-center gap-2 text-sm"><input type="hidden" name="obrigatorio" value="0"><input type="checkbox" name="obrigatorio" value="1" @checked(old('obrigatorio', $campo->obrigatorio))> Obrigatório</label>
                <label class="flex items-center gap-2 text-sm"><input type="hidden" name="ativo" value="0"><input type="checkbox" name="ativo" value="1" @checked(old('ativo', $campo->ativo))> Ativo</label>
            </div>
            <div><label class="rotulo" for="opcoes-{{ $campo->id }}">Opções (uma por linha)</label><textarea id="opcoes-{{ $campo->id }}" class="campo" name="opcoes_texto" rows="3" placeholder="Aplicável a campos de seleção">{{ implode("\n", $campo->opcoes ?? []) }}</textarea></div>
        </form>
    @endforeach

    <section class="cartao">
        <h2 class="mb-4 font-semibold">Adicionar campo personalizado</h2>
        <form method="POST" action="{{ route('admin.formulario-publico.criar') }}" class="grid gap-4 md:grid-cols-2">
            @csrf
            <div><label class="rotulo" for="novo-rotulo">Nome do campo</label><input id="novo-rotulo" class="campo" name="rotulo" value="{{ old('rotulo') }}" maxlength="120" required></div>
            <div><label class="rotulo" for="novo-tipo">Tipo</label><select id="novo-tipo" name="tipo" class="campo">@foreach ($tipos as $tipo)<option value="{{ $tipo }}">{{ str_replace('_', ' ', ucfirst($tipo)) }}</option>@endforeach</select></div>
            <div class="md:col-span-2"><label class="rotulo" for="nova-descricao">Orientação</label><textarea id="nova-descricao" name="descricao" class="campo" rows="2" maxlength="500">{{ old('descricao') }}</textarea></div>
            <div><label class="rotulo" for="nova-ordem">Ordem de exibição</label><input id="nova-ordem" type="number" class="campo" name="ordem" min="0" max="65535" value="{{ old('ordem', ($campos->max('ordem') ?? 0) + 10) }}" required></div>
            <div><label class="rotulo" for="novo-max">Tamanho máximo</label><input id="novo-max" type="number" class="campo" name="tamanho_maximo" min="1" max="100000"></div>
            <div><label class="rotulo" for="novas-opcoes">Opções (uma por linha)</label><textarea id="novas-opcoes" name="opcoes_texto" class="campo" rows="3"></textarea></div>
            <div class="flex items-center gap-2"><input type="hidden" name="obrigatorio" value="0"><input id="novo-obrigatorio" type="checkbox" name="obrigatorio" value="1"><label for="novo-obrigatorio" class="text-sm">Obrigatório</label></div>
            <div class="md:col-span-2 flex justify-end"><button class="btn-primario">Adicionar campo</button></div>
        </form>
    </section>
</div>
@endsection
