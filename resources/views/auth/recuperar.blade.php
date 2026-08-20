@extends('layouts.app')
@section('title', 'Recuperar palavra-passe')
@section('body-class', 'pagina-entrar')
@section('conteudo-class', 'conteudo--estreito entrar')
@section('sem-resumo-erros', '1')

@section('content')
<div class="cartao">
    <h1>Recuperar palavra-passe</h1>
    <p class="intro">Indique o email da sua conta. Enviamos-lhe um link para definir uma nova palavra-passe.</p>

    <form method="post" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="campo">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="username" autofocus
                   @error('email') aria-invalid="true" aria-describedby="email-erro" @enderror>
            @error('email')
                <p class="erro" id="email-erro" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn--primario" style="width:100%">Enviar link</button>
    </form>

    <p class="meta" style="margin:1.25rem 0 0">
        <a href="{{ route('login') }}">← Voltar à página de entrada</a>
    </p>
</div>
@endsection
