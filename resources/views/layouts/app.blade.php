<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#081D10">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>
    {{-- Tipo de letra opcional (Inter). Se não houver internet, cai para a letra do sistema. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="icon" href="{{ asset('img/icon-192.png') }}" sizes="192x192">
    <link rel="apple-touch-icon" href="{{ asset('img/apple-touch-icon.png') }}">
</head>
<body class="@yield('body-class')" @yield('body-attrs')>
<a class="skip-link" href="#conteudo">Saltar para o conteúdo</a>

<header class="barra no-print">
    <div class="barra__inner">
        <a class="barra__marca" href="{{ route('consulta') }}">
            <span class="barra__marca-logo">
                <img class="barra__logo" src="{{ asset('img/nexus-1.png') }}" alt="Nexus" width="150" height="28">
                <span class="barra__marca-sub">Technical Suite</span>
            </span>
        </a>
        <nav class="barra__nav" aria-label="Navegação principal">
            <a href="{{ config('app.portal_url') }}">← Aplicações</a>
            <a href="{{ route('consulta') }}" @if(request()->routeIs('consulta')) aria-current="page" @endif>Consulta</a>
            @auth
                @can('editar')
                    <a href="{{ route('admin.procedimentos.index') }}" @if(request()->is('admin*')) aria-current="page" @endif>Administração</a>
                @endcan
                <span class="barra__user">{{ auth()->user()->name }}@if(auth()->user()->area_label) · {{ auth()->user()->area_label }}@endif</span>
                <form method="post" action="{{ config('app.portal_url') }}/sair" class="inline">
                    @csrf
                    <button type="submit">Sair</button>
                </form>
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

@yield('hero')

<main id="conteudo" tabindex="-1">
    <div class="conteudo @yield('conteudo-class')">
        @if(session('status'))
            <div class="alerta alerta--ok no-print" role="status">
                <svg class="alerta__icone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg>
                <div>{{ session('status') }}</div>
            </div>
        @endif

        @if($errors->any() && ! View::hasSection('sem-resumo-erros'))
            <div class="alerta alerta--erro no-print" role="alert">
                <svg class="alerta__icone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
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
    Nexus Solutions · Uso interno
</footer>

<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
</body>
</html>
