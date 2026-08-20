@extends('layouts.app')
@section('title', 'Definir palavra-passe')
@section('body-class', 'pagina-entrar')
@section('conteudo-class', 'conteudo--estreito entrar')
@section('sem-resumo-erros', '1')

@section('content')
<div class="cartao">
    <h1>Definir palavra-passe</h1>
    <p class="intro">Escolha a palavra-passe para a sua conta (mínimo 10 caracteres).</p>

    <form method="post" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="campo">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username"
                   @error('email') aria-invalid="true" aria-describedby="email-erro" @enderror>
            @error('email')
                <p class="erro" id="email-erro" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="campo">
            <label for="password">Nova palavra-passe</label>
            <input type="password" id="password" name="password" required minlength="10" autocomplete="new-password" autofocus
                   @error('password') aria-invalid="true" aria-describedby="password-erro" @enderror>
            @error('password')
                <p class="erro" id="password-erro" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="campo">
            <label for="password_confirmation">Repetir a palavra-passe</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required minlength="10" autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn--primario" style="width:100%">Guardar e entrar</button>
    </form>

    <p class="meta" style="margin:1.25rem 0 0">
        Link expirado? <a href="{{ route('password.request') }}">Peça um novo aqui</a>.
    </p>
</div>
@endsection
