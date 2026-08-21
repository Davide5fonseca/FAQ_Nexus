@extends('layouts.app')
@section('title', 'Perfil de '.$utilizador->name)
@section('conteudo-class', 'conteudo--estreito')

@section('content')
<div class="cabecalho-pagina">
    <h1>Perfil de {{ $utilizador->name }}</h1>
    <div class="accoes">
        <a class="btn btn--secundario" href="{{ route('admin.utilizadores.index') }}">← Voltar</a>
    </div>
</div>

<p class="meta">{{ $utilizador->email }}</p>

<form method="post" action="{{ route('admin.utilizadores.update', $utilizador->id) }}" class="cartao" novalidate>
    @csrf @method('PUT')

    <fieldset class="campo">
        <legend class="legenda">Área</legend>
        <div class="radios">
            @foreach(\App\Models\User::AREAS as $chave => $etiqueta)
                <label>
                    <input type="radio" name="area" value="{{ $chave }}"
                           @checked(old('area', $perfil?->area ?? 'tecnica') === $chave)>
                    {{ $etiqueta }}
                </label>
            @endforeach
        </div>
        @error('area')<p class="erro">{{ $message }}</p>@enderror
        <p class="ajuda">Só verá procedimentos desta área.</p>
    </fieldset>

    <fieldset class="campo">
        <legend class="legenda">Perfil</legend>
        <div class="radios">
            @foreach(\App\Models\User::ROLES as $chave => $etiqueta)
                <label>
                    <input type="radio" name="papel" value="{{ $chave }}"
                           @checked(old('papel', $perfil?->papel ?? 'leitor') === $chave)>
                    {{ $etiqueta }}
                </label>
            @endforeach
        </div>
        @error('papel')<p class="erro">{{ $message }}</p>@enderror
        <ul class="ajuda" style="margin:.4rem 0 0;padding-left:1.1rem">
            @foreach(\App\Models\User::ROLES as $chave => $etiqueta)
                <li><strong>{{ $etiqueta }}</strong> — {{ \App\Models\User::ROLES_DESCRICAO[$chave] }}</li>
            @endforeach
        </ul>
    </fieldset>

    <div class="accoes-form">
        <button type="submit" class="btn btn--primario">Guardar perfil</button>
        <a class="btn btn--secundario" href="{{ route('admin.utilizadores.index') }}">Cancelar</a>
    </div>
</form>
@endsection
