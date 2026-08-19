@extends('layouts.app')
@section('title', 'Consulta')

@section('content')
<div data-consulta>

    @if($rules->isNotEmpty())
        <section class="regras" aria-labelledby="regras-titulo">
            <h2 id="regras-titulo">Regras de segurança</h2>
            <ol>
                @foreach($rules as $rule)
                    <li>{{ $rule->content }}</li>
                @endforeach
            </ol>
        </section>
    @endif

    <div class="cabecalho-pagina no-print">
        <h1>Procedimentos</h1>
        @auth
            <div class="accoes">
                <a class="btn btn--primario" href="{{ route('admin.procedimentos.create') }}">Novo procedimento</a>
            </div>
        @endauth
    </div>

    @if(! $hasAny)
        <div class="vazio">
            <h2>Ainda não há procedimentos.</h2>
            @auth
                <p>Comece por criar o primeiro procedimento de reparação.</p>
                <a class="btn btn--primario" href="{{ route('admin.procedimentos.create') }}">Criar o primeiro</a>
            @else
                <p>Quando o responsável os inserir, aparecem aqui.</p>
                <a class="btn btn--secundario" href="{{ route('login') }}">Entrar na administração</a>
            @endauth
        </div>
    @else
        <form class="filtros no-print" method="get" action="{{ route('consulta') }}" role="search" aria-label="Filtrar procedimentos">
            <div class="campo">
                <label for="q">Pesquisar</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] }}" placeholder="Título, passo, sintoma…" autocomplete="off">
            </div>
            <div class="campo">
                <label for="categoria">Categoria</label>
                <select id="categoria" name="categoria">
                    <option value="">Todas</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected($filters['categoria'] === $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="campo">
                <label for="nivel">Nível</label>
                <select id="nivel" name="nivel">
                    <option value="">Todos</option>
                    @foreach(\App\Models\Procedure::LEVELS as $lvl)
                        <option value="{{ $lvl }}" @selected($filters['nivel'] === $lvl)>Nível {{ $lvl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filtros__accoes">
                <button type="submit" class="btn btn--escuro">Filtrar</button>
                <a href="{{ route('consulta') }}" class="btn btn--secundario" data-limpar>Limpar</a>
            </div>
        </form>

        @php
            $qs = http_build_query(array_filter(['q' => $filters['q'], 'categoria' => $filters['categoria'], 'nivel' => $filters['nivel']]));
        @endphp

        <div class="resumo-lista no-print">
            <span class="contagem" data-contagem aria-live="polite">
                @if($procedures->isEmpty())
                    Nenhum procedimento corresponde aos filtros.
                @else
                    {{ $procedures->count() }} {{ $procedures->count() === 1 ? 'procedimento' : 'procedimentos' }}
                @endif
            </span>
            <div class="accoes">
                <button type="button" class="btn btn--secundario btn--pequeno" data-expandir>Expandir todos</button>
                <button type="button" class="btn btn--secundario btn--pequeno" data-recolher>Recolher todos</button>
                <a class="btn btn--secundario btn--pequeno" data-imprimir-todos data-base="{{ route('imprimir') }}"
                   href="{{ route('imprimir') }}{{ $qs ? '?'.$qs : '' }}">Imprimir lista</a>
            </div>
        </div>

        <div class="vazio" data-sem-resultados @if($procedures->isNotEmpty()) hidden @endif>
            <h2>Nenhum procedimento corresponde aos filtros.</h2>
            <p>Experimente outro termo ou <a href="{{ route('consulta') }}">limpe os filtros</a>.</p>
        </div>

        @foreach($procedures as $p)
            <details class="proc" id="proc-{{ $p->reference_number }}"
                     data-categoria="{{ $p->category_id }}"
                     data-nivel="{{ $p->level }}"
                     data-texto="{{ $p->reference }} {{ $p->title }} {{ $p->category->name }} {{ $p->steps->pluck('content')->implode(' ') }} {{ $p->ticket_notes }} {{ $p->escalation }}">
                <summary>
                    <svg class="proc__seta" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                    <span class="proc__titulo">{{ $p->title }}</span>
                    <span class="proc__tags">
                        <span class="etiqueta etiqueta--ref">{{ $p->reference }}</span>
                        <span class="etiqueta">{{ $p->category->name }}</span>
                        <span class="etiqueta etiqueta--nivel-{{ $p->level }}">Nível {{ $p->level }}</span>
                    </span>
                </summary>
                <div class="proc__corpo">
                    <h3>Passos</h3>
                    @if($p->steps->isEmpty())
                        <p class="meta">Este procedimento ainda não tem passos.</p>
                    @else
                        <ol class="proc__passos">
                            @foreach($p->steps as $step)
                                <li>{{ $step->content }}</li>
                            @endforeach
                        </ol>
                    @endif

                    @if(filled($p->ticket_notes))
                        <h3>O que registar no ticket</h3>
                        <p class="proc__texto">{{ $p->ticket_notes }}</p>
                    @endif

                    @if(filled($p->escalation))
                        <h3>Quando escalar</h3>
                        <p class="proc__texto">{{ $p->escalation }}</p>
                    @endif

                    <div class="proc__rodape">
                        <span class="meta">
                            Última alteração: {{ $p->updated_at->format('d/m/Y H:i') }}@if($p->updated_by) por {{ $p->updated_by }}@endif
                            · Criado em {{ $p->created_at->format('d/m/Y') }}
                        </span>
                        <span class="accoes no-print">
                            <a class="btn btn--secundario btn--pequeno" href="{{ route('imprimir.um', $p) }}">Imprimir</a>
                            @auth
                                <a class="btn btn--secundario btn--pequeno" href="{{ route('admin.procedimentos.edit', $p) }}">Editar</a>
                            @endauth
                        </span>
                    </div>
                </div>
            </details>
        @endforeach
    @endif
</div>
@endsection
