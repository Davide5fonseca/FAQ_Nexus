@extends('layouts.app')
@section('title', 'Recuperar palavra-passe')
@section('body-class', 'pagina-entrar')
@section('conteudo-class', 'conteudo--estreito entrar')
@section('sem-resumo-erros', '1')

@section('content')
@include('auth.marca')

<div class="cartao">
    <h1>Recuperar palavra-passe</h1>
    <p class="intro">Indique o email da sua conta e enviamos-lhe um link para definir uma nova.</p>

    <form method="post" action="{{ route('password.email') }}" novalidate>
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

        <button type="submit" class="btn btn--primario btn--bloco">Enviar link</button>
    </form>

    <p class="entrar__ajuda">
        <a href="{{ route('login') }}">← Voltar</a>
    </p>
</div>

<p class="entrar__rodape">Nexus Solutions · Uso interno</p>
@endsection
