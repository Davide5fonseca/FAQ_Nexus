@extends('layouts.app')
@section('title', 'Definir palavra-passe')
@section('body-class', 'pagina-entrar')
@section('conteudo-class', 'conteudo--estreito entrar')
@section('sem-resumo-erros', '1')

@section('content')
@include('auth.marca')

<div class="cartao">
    <h1>Definir palavra-passe</h1>
    <p class="intro">Escolha a palavra-passe da sua conta. Mínimo 10 caracteres.</p>

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

        @include('auth.campo-password', [
            'id' => 'password',
            'rotulo' => 'Nova palavra-passe',
            'autocomplete' => 'new-password',
            'minlength' => 10,
            'autofocus' => true,
        ])

        @include('auth.campo-password', [
            'id' => 'password_confirmation',
            'rotulo' => 'Repetir a palavra-passe',
            'autocomplete' => 'new-password',
            'minlength' => 10,
        ])

        <button type="submit" class="btn btn--primario btn--bloco">Guardar e entrar</button>
    </form>

    <p class="entrar__ajuda">
        Link expirado? <a href="{{ route('password.request') }}">Peça um novo</a>
    </p>
</div>

<p class="entrar__rodape">Nexus Solutions · Uso interno</p>
@endsection
