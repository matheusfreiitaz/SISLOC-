@extends('layouts.app')
@section('titulo', 'Novo chamado')
@section('conteudo')
<form method="POST" action="{{ route('chamados.store') }}" enctype="multipart/form-data" class="cartao mx-auto max-w-3xl space-y-4">
    @csrf
    @if ($solicitantes->isNotEmpty())
        <div><label class="rotulo">Solicitante</label>
            <select class="campo" name="solicitante_id"><option value="">Eu mesmo</option>
                @foreach ($solicitantes as $u)<option value="{{ $u->id }}" @selected(old('solicitante_id') == $u->id)>{{ $u->nome }} — {{ $u->email }}</option>@endforeach</select></div>
    @endif
    <x-campo nome="assunto" rotulo="Assunto" required />
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="rotulo">Departamento</label><select class="campo" name="departamento_id" required>
            @foreach ($departamentos as $d)<option value="{{ $d->id }}" @selected(old('departamento_id') == $d->id)>{{ $d->nome }}</option>@endforeach</select></div>
        <div><label class="rotulo">Prioridade</label><select class="campo" name="prioridade_id" required>
            @foreach ($prioridades as $p)<option value="{{ $p->id }}" @selected(old('prioridade_id') == $p->id)>{{ $p->nome }} ({{ $p->prazo_horas }}h)</option>@endforeach</select></div>
        <div><label class="rotulo">Categoria</label><select class="campo" name="categoria_id" id="categoria"><option value="">—</option>
            @foreach ($categorias as $c)<option value="{{ $c->id }}">{{ $c->nome }}</option>@endforeach</select></div>
        <div><label class="rotulo">Subcategoria</label><select class="campo" name="subcategoria_id" id="subcategoria"><option value="">—</option>
            @foreach ($categorias as $c)@foreach ($c->subcategorias as $s)<option value="{{ $s->id }}" data-pai="{{ $c->id }}">{{ $s->nome }}</option>@endforeach @endforeach</select></div>
    </div>
    <div><label class="rotulo">Descrição detalhada</label><textarea class="campo" name="descricao" rows="6" required>{{ old('descricao') }}</textarea>
        @error('descricao')<p class="erro">{{ $message }}</p>@enderror</div>
    <div><label class="rotulo">Anexos (até 5 arquivos de 10 MB)</label><input type="file" name="anexos[]" multiple class="campo"></div>
    <div class="flex justify-end gap-2"><a href="{{ route('chamados.index') }}" class="btn-neutro">Cancelar</a><button class="btn-primario">Abrir chamado</button></div>
</form>
<script>
    // Filtra as subcategorias conforme a categoria escolhida.
    const cat = document.getElementById('categoria'), sub = document.getElementById('subcategoria');
    function filtrar() { [...sub.options].forEach(o => { o.hidden = o.value && o.dataset.pai !== cat.value; }); sub.value = ''; }
    cat.addEventListener('change', filtrar); filtrar();
</script>
@endsection
