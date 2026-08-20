@extends('layouts.app')
@section('title', 'Entrar')
@section('body-class', 'pagina-entrar')
@section('conteudo-class', 'conteudo--estreito entrar')
@section('sem-resumo-erros', '1')

@section('content')
@include('auth.marca')

<div class="cartao">
    <h1>Entrar</h1>

    <form method="post" action="{{ route('login.submit') }}" novalidate>
        @csrf

        <div class="campo">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="username" autofocus
                   placeholder="nome@nxs.pt"
                   @error('email') aria-invalid="true" aria-describedby="email-erro" @enderror>
            @error('email')
                <p class="erro" id="email-erro" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="campo">
            <div class="campo__topo">
                <label for="password">Palavra-passe</label>
                <button type="button" class="btn--ligacao" data-ver-password="password" aria-pressed="false">Mostrar</button>
            </div>
            <input type="password" id="password" name="password" required autocomplete="current-password"
                   @error('password') aria-invalid="true" aria-describedby="password-erro" @enderror>
            @error('password')
                <p class="erro" id="password-erro" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn--primario btn--bloco">Entrar</button>
    </form>

    <p class="entrar__ajuda">
        <a href="{{ route('password.request') }}">Esqueci-me da palavra-passe</a>
    </p>
</div>

<p class="entrar__rodape">Nexus Solutions · Uso interno</p>
@endsection
