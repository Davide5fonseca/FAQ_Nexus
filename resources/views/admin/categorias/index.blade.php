@extends('layouts.app')
@section('title', 'Administração · Categorias')

@section('content')
<div class="cabecalho-pagina">
    <h1>Categorias</h1>
</div>

<div class="cartao">
    <h2>Nova categoria</h2>
    <form method="post" action="{{ route('admin.categorias.store') }}" novalidate style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:flex-start">
        @csrf
        <div class="campo" style="flex:1 1 240px;margin:0">
            <label for="name" class="visually-hidden">Nome da categoria</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="80" placeholder="Ex.: Impressoras, Redes, Portáteis…"
                   @error('name') aria-invalid="true" aria-describedby="name-erro" @enderror>
            @error('name')<p class="erro" id="name-erro">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn btn--primario">Adicionar</button>
    </form>
</div>

@if($categories->isEmpty())
    <div class="vazio">
        <h2>Ainda não há categorias.</h2>
        <p>Adicione a primeira no campo acima. Os procedimentos precisam de uma categoria.</p>
    </div>
@else
    <h2>{{ $categories->count() }} {{ $categories->count() === 1 ? 'categoria' : 'categorias' }}</h2>
    <ul class="lista-edit">
        @foreach($categories as $cat)
            <li>
                <form method="post" action="{{ route('admin.categorias.update', $cat) }}" class="editar" novalidate>
                    @csrf @method('PUT')
                    <label for="cat-{{ $cat->id }}" class="visually-hidden">Nome da categoria</label>
                    <input type="text" id="cat-{{ $cat->id }}" name="name" value="{{ $cat->name }}" required maxlength="80">
                    <button type="submit" class="btn btn--secundario">Guardar</button>
                </form>
                <span class="contagem">
                    {{ $cat->procedures_count }} {{ $cat->procedures_count === 1 ? 'procedimento' : 'procedimentos' }}
                </span>
                <form method="post" action="{{ route('admin.categorias.destroy', $cat) }}"
                      data-confirm="Apagar a categoria «{{ $cat->name }}»?">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn--perigo" @if($cat->procedures_count > 0) title="Tem procedimentos associados; mude-os primeiro de categoria" @endif>Apagar</button>
                </form>
            </li>
        @endforeach
    </ul>
@endif
@endsection
