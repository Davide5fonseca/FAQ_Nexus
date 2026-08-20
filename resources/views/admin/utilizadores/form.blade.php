@extends('layouts.app')
@php $editing = $user->exists; @endphp
@section('title', $editing ? 'Editar conta' : 'Nova conta')
@section('conteudo-class', 'conteudo--estreito')

@section('content')
<div class="cabecalho-pagina">
    <h1>{{ $editing ? 'Editar conta' : 'Nova conta' }}</h1>
    <div class="accoes">
        <a class="btn btn--secundario" href="{{ route('admin.utilizadores.index') }}">← Voltar</a>
    </div>
</div>

<form method="post" action="{{ $editing ? route('admin.utilizadores.update', $user) : route('admin.utilizadores.store') }}" class="cartao" novalidate>
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="campo">
        <label for="name">Nome</label>
        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="120" autofocus
               @error('name') aria-invalid="true" aria-describedby="name-erro" @enderror>
        @error('name')<p class="erro" id="name-erro">{{ $message }}</p>@enderror
    </div>

    <div class="campo">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="190" autocomplete="off"
               @error('email') aria-invalid="true" aria-describedby="email-erro" @enderror>
        @error('email')<p class="erro" id="email-erro">{{ $message }}</p>@enderror
    </div>

    <fieldset class="campo">
        <legend class="legenda">Área</legend>
        <div class="radios">
            @foreach(\App\Models\User::AREAS as $key => $label)
                <label><input type="radio" name="area" value="{{ $key }}" @checked(old('area', $user->area) === $key)> {{ $label }}</label>
            @endforeach
        </div>
        @error('area')<p class="erro">{{ $message }}</p>@enderror
    </fieldset>

    <fieldset class="campo">
        <legend class="legenda">Perfil</legend>
        <div class="radios">
            @foreach(\App\Models\User::ROLES as $key => $label)
                <label><input type="radio" name="role" value="{{ $key }}" @checked(old('role', $user->role) === $key)> {{ $label }}</label>
            @endforeach
        </div>
        @error('role')<p class="erro">{{ $message }}</p>@enderror
        <ul class="ajuda" style="margin:.4rem 0 0;padding-left:1.1rem">
            @foreach(\App\Models\User::ROLES as $key => $label)
                <li><strong>{{ $label }}</strong> — {{ \App\Models\User::ROLES_DESCRICAO[$key] }}</li>
            @endforeach
        </ul>
    </fieldset>

    @if($editing)
        <div class="campo">
            <label for="password">Nova palavra-passe (deixe em branco para manter)</label>
            <input type="password" id="password" name="password" minlength="10" autocomplete="new-password"
                   @error('password') aria-invalid="true" aria-describedby="password-erro" @enderror>
            @error('password')<p class="erro" id="password-erro">{{ $message }}</p>@enderror
            <p class="ajuda">Normalmente não é preciso: a pessoa pode usar "Esqueci-me da palavra-passe".</p>
        </div>
    @else
        <div class="alerta alerta--ok" role="note" style="margin-bottom:1.1rem">
            <svg class="alerta__icone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
            <div>A pessoa recebe um <strong>email</strong> para definir a palavra-passe (válido 3 dias).</div>
        </div>
    @endif

    @if($editing)
        <div class="campo">
            <label style="display:inline-flex;gap:.5rem;align-items:center;font-weight:500">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" @checked(old('active', $user->active))> Conta activa
            </label>
            <p class="ajuda">Uma conta desactivada não consegue entrar.</p>
        </div>
    @endif

    <div class="accoes-form">
        <button type="submit" class="btn btn--primario">{{ $editing ? 'Guardar' : 'Criar conta' }}</button>
        <a class="btn btn--secundario" href="{{ route('admin.utilizadores.index') }}">Cancelar</a>
    </div>
</form>
@endsection
