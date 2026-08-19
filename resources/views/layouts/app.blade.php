<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='6' fill='%232FBC5E'/><text x='16' y='22' font-family='sans-serif' font-weight='800' font-size='15' text-anchor='middle' fill='%23081D10'>NX</text></svg>">
</head>
<body @yield('body-attrs')>
<a class="skip-link" href="#conteudo">Saltar para o conteúdo</a>

<header class="barra no-print">
    <div class="barra__inner">
        <a class="barra__marca" href="{{ route('consulta') }}">
            <span class="barra__logo" aria-hidden="true">NX</span>
            <span class="barra__titulo">
                <span>Base de Procedimentos Técnicos</span>
                <small>Nexus Solutions</small>
            </span>
        </a>
        <nav class="barra__nav" aria-label="Navegação principal">
            <a href="{{ route('consulta') }}" @if(request()->routeIs('consulta')) aria-current="page" @endif>Consulta</a>
            @auth
                <a href="{{ route('admin.procedimentos.index') }}" @if(request()->is('admin*')) aria-current="page" @endif>Administração</a>
                <span class="barra__user">{{ auth()->user()->name }}</span>
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
                <a href="{{ route('admin.categorias.index') }}" @if(request()->routeIs('admin.categorias.*')) aria-current="page" @endif>Categorias</a>
                <a href="{{ route('admin.regras.index') }}" @if(request()->routeIs('admin.regras.*')) aria-current="page" @endif>Regras de segurança</a>
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
