@extends('layouts.app')
@section('title', 'Entrar')
@section('body-class', 'pagina-entrar')
@section('conteudo-class', 'conteudo--estreito entrar')
@section('sem-resumo-erros', '1')

@section('content')
<div class="cartao">
    <h1>Entrar na administração</h1>
    <p class="intro">Para técnicos e produção carregarem problemas e soluções.</p>

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
        Esqueceu-se da palavra-passe? Peça a um administrador para a repor em Administração → Utilizadores.
    </p>
</div>
<p style="text-align:center;margin-top:1rem"><a href="{{ route('consulta') }}" style="color:#C9E4D2">← Voltar à consulta</a></p>
@endsection
