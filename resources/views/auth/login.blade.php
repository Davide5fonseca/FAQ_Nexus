@extends('layouts.app')
@section('title', 'Entrar')
@section('body-class', 'pagina-entrar')
@section('conteudo-class', 'conteudo--estreito entrar')
@section('sem-resumo-erros', '1')

@section('content')
<div class="cartao">
    <h1>Entrar</h1>

    <form method="post" action="{{ route('login.submit') }}" novalidate>
        @csrf

        <div class="campo">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="username" autofocus
                   @error('email') aria-invalid="true" aria-describedby="email-erro" @enderror>
            @error('email')
                <p class="erro" id="email-erro" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="campo">
            <label for="password">Palavra-passe</label>
            <input type="password" id="password" name="password" required autocomplete="current-password"
                   @error('password') aria-invalid="true" aria-describedby="password-erro" @enderror>
            @error('password')
                <p class="erro" id="password-erro" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn--primario" style="width:100%">Entrar</button>
    </form>

    <p class="meta" style="margin:1.25rem 0 0">
        <a href="{{ route('password.request') }}">Esqueci-me da palavra-passe</a>
    </p>
</div>
@endsection
