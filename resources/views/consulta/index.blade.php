@extends('layouts.app')
@section('title', 'Consulta')
@section('conteudo-class', 'conteudo--sem-topo')

@section('hero')
<div class="hero no-print">
    <div class="hero__inner">
        <div class="hero__topo">
            <div>
                <h1>Procedimentos técnicos</h1>
                <p>Problemas e soluções de reparação, passo a passo, alimentados pela área técnica e pela produção. Pesquise por sintoma, equipamento ou título.</p>
                <div class="hero__stats" aria-label="Resumo">
                    <span class="hero__stat"><strong>{{ $procedures->count() }}</strong> {{ $procedures->count() === 1 ? 'procedimento' : 'procedimentos' }}</span>
                    <span class="hero__stat"><strong>{{ $categories->count() }}</strong> {{ $categories->count() === 1 ? 'categoria' : 'categorias' }}</span>
                    @if($rules->isNotEmpty())<span class="hero__stat"><strong>{{ $rules->count() }}</strong> {{ $rules->count() === 1 ? 'regra de segurança' : 'regras de segurança' }}</span>@endif
                </div>
            </div>
            @auth
                <a class="btn btn--primario" href="{{ route('admin.procedimentos.create') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Novo procedimento
                </a>
            @endauth
        </div>
    </div>
</div>
@endsection

@section('content')
<div data-consulta class="sobreposto">

    @if(! $hasAny)
        <div class="vazio">
            <div class="vazio__icone" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1"/><path d="M10 12h4M10 16h4M10 8h4"/></svg>
            </div>
            <h2>Ainda não há procedimentos.</h2>
            @auth
                <p>Comece por criar o primeiro procedimento: o problema, a solução passo a passo e o que registar no ticket.</p>
                <div class="accoes"><a class="btn btn--primario" href="{{ route('admin.procedimentos.create') }}">Criar o primeiro</a></div>
            @else
                <p>Quando a área técnica ou a produção os inserir, aparecem aqui. Tem conta? Entre para começar a carregar.</p>
                <div class="accoes"><a class="btn btn--secundario" href="{{ route('login') }}">Entrar na administração</a></div>
            @endauth
        </div>
    @else
        <form class="filtros no-print" method="get" action="{{ route('consulta') }}" role="search" aria-label="Filtrar procedimentos">
            <div class="campo">
                <label for="q">Pesquisar</label>
                <div class="pesquisa">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" id="q" name="q" value="{{ $filters['q'] }}" placeholder="Sintoma, equipamento, título, passo…" autocomplete="off">
                </div>
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
            <div class="filtros__accoes">
                <button type="submit" class="btn btn--escuro">Filtrar</button>
                <a href="{{ route('consulta') }}" class="btn btn--secundario" data-limpar>Limpar</a>
            </div>
        </form>

        @if($rules->isNotEmpty())
            <section class="regras" aria-labelledby="regras-titulo">
                <div class="regras__icone" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v6c0 5 3.4 9.4 8 11 4.6-1.6 8-6 8-11V5z"/><path d="m9 12 2 2 4-4"/></svg>
                </div>
                <div>
                    <h2 id="regras-titulo">Regras de segurança</h2>
                    <ol>
                        @foreach($rules as $rule)
                            <li>{{ $rule->content }}</li>
                        @endforeach
                    </ol>
                </div>
            </section>
        @endif

        @php
            $qs = http_build_query(array_filter(['q' => $filters['q'], 'categoria' => $filters['categoria']]));
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
                   href="{{ route('imprimir') }}{{ $qs ? '?'.$qs : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v7H6z"/></svg>
                    Imprimir lista
                </a>
            </div>
        </div>

        <div class="vazio" data-sem-resultados @if($procedures->isNotEmpty()) hidden @endif>
            <div class="vazio__icone" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M8.5 11h5"/></svg>
            </div>
            <h2>Nenhum procedimento corresponde aos filtros.</h2>
            <p>Experimente outro termo ou <a href="{{ route('consulta') }}">limpe os filtros</a>.</p>
        </div>

        @foreach($procedures as $p)
            <details class="proc" id="proc-{{ $p->reference_number }}"
                     data-categoria="{{ $p->category_id }}"
                     data-texto="{{ $p->reference }} {{ $p->title }} {{ $p->problem }} {{ $p->category->name }} {{ $p->steps->pluck('content')->implode(' ') }} {{ $p->ticket_notes }} {{ $p->escalation }}">
                <summary>
                    <svg class="proc__seta" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                    <span class="proc__titulo">{{ $p->title }}</span>
                    <span class="proc__tags">
                        <span class="etiqueta etiqueta--ref">{{ $p->reference }}</span>
                        <span class="etiqueta">{{ $p->category->name }}</span>
                    </span>
                </summary>
                <div class="proc__corpo">
                    <div class="proc__grelha">
                        <div>
                            @if(filled($p->problem))
                                <h3>Problema / sintomas</h3>
                                <p class="proc__problema">{{ $p->problem }}</p>
                            @endif

                            <h3>Solução — passos</h3>
                            @if($p->steps->isEmpty())
                                <p class="meta">Este procedimento ainda não tem passos.</p>
                            @else
                                <ol class="proc__passos">
                                    @foreach($p->steps as $step)
                                        <li>{{ $step->content }}</li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>
                        <aside class="proc__lateral">
                            @if(filled($p->ticket_notes))
                                <div class="caixa-info caixa-info--ticket">
                                    <h3>O que registar no ticket</h3>
                                    <p>{{ $p->ticket_notes }}</p>
                                </div>
                            @endif
                            @if(filled($p->escalation))
                                <div class="caixa-info caixa-info--escalar">
                                    <h3>Quando escalar</h3>
                                    <p>{{ $p->escalation }}</p>
                                </div>
                            @endif
                            <div class="caixa-info caixa-info--ficha">
                                <h3>Ficha</h3>
                                <p class="meta">
                                    Referência <strong>{{ $p->reference }}</strong><br>
                                    Categoria {{ $p->category->name }}<br>
                                    Criado em {{ $p->created_at->format('d/m/Y') }}@if($p->created_by) por {{ $p->created_by }}@endif<br>
                                    Última alteração {{ $p->updated_at->format('d/m/Y H:i') }}@if($p->updated_by) por {{ $p->updated_by }}@endif
                                </p>
                            </div>
                        </aside>
                    </div>

                    <div class="proc__rodape">
                        <span class="meta">Última alteração: {{ $p->updated_at->format('d/m/Y H:i') }}@if($p->updated_by) por {{ $p->updated_by }}@endif</span>
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
