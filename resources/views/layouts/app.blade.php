<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='6' fill='%23081D10'/><path d='M8 25V7l16 18V7' fill='none' stroke='%232FBC5E' stroke-width='3.5' stroke-linecap='round' stroke-linejoin='round'/></svg>">
</head>
<body @yield('body-attrs')>
<a class="skip-link" href="#conteudo">Saltar para o conteúdo</a>

<header class="barra no-print">
    <div class="barra__inner">
        <a class="barra__marca" href="{{ route('consulta') }}">
            <img class="barra__logo" src="{{ asset('img/logo.svg') }}" alt="Nexus Technical Suite" width="150" height="58">
            <span class="barra__titulo">Base de Procedimentos Técnicos</span>
        </a>
        <nav class="barra__nav" aria-label="Navegação principal">
            <a href="{{ route('consulta') }}" @if(request()->routeIs('consulta')) aria-current="page" @endif>Consulta</a>
            @auth
                <a href="{{ route('admin.procedimentos.index') }}" @if(request()->is('admin*')) aria-current="page" @endif>Administração</a>
                <span class="barra__user">{{ auth()->user()->name }}@if(auth()->user()->area_label) · {{ auth()->user()->area_label }}@endif</span>
                <form method="post" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit">Sair</button>
                </form>
            @else
                <a href="{{ route('login') }}" @if(request()->routeIs('login')) aria-current="page" @endif>Administração</a>
            @endauth
        </nav>
    </div>
</header>

@auth
    @if(request()->is('admin*'))
        <nav class="subnav no-print" aria-label="Secções da administração">
            <div class="subnav__inner">
                <a href="{{ route('admin.procedimentos.index') }}" @if(request()->routeIs('admin.procedimentos.*')) aria-current="page" @endif>Procedimentos</a>
                @can('admin')
                    <a href="{{ route('admin.categorias.index') }}" @if(request()->routeIs('admin.categorias.*')) aria-current="page" @endif>Categorias</a>
                    <a href="{{ route('admin.regras.index') }}" @if(request()->routeIs('admin.regras.*')) aria-current="page" @endif>Regras de segurança</a>
                    <a href="{{ route('admin.utilizadores.index') }}" @if(request()->routeIs('admin.utilizadores.*')) aria-current="page" @endif>Utilizadores</a>
                @endcan
            </div>
        </nav>
    @endif
@endauth

<main id="conteudo" tabindex="-1">
    <div class="conteudo @yield('conteudo-class')">
        @if(session('status'))
            <div class="alerta alerta--ok no-print" role="status">
                <span aria-hidden="true">✓</span>
                <div>{{ session('status') }}</div>
            </div>
        @endif

        @if($errors->any() && ! View::hasSection('sem-resumo-erros'))
            <div class="alerta alerta--erro no-print" role="alert">
                <span aria-hidden="true">!</span>
                <div>
                    <strong>Não foi possível concluir. Verifique:</strong>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
    </div>
</main>

<footer class="rodape no-print">
    Nexus Solutions · Base de Procedimentos Técnicos · Uso interno
</footer>

<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
</body>
</html>
