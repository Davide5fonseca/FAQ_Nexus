@extends('layouts.app')
@section('title', 'Administração · Procedimentos')

@section('hero')
<div class="hero hero--compacto">
    <div class="hero__inner hero__topo">
        <h1>Procedimentos</h1>
        <a class="btn btn--primario" href="{{ route('admin.procedimentos.create') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Novo procedimento
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="resumo" aria-label="Resumo">
    <div class="resumo__item"><span>Activos</span><strong>{{ $counts['activos'] }}</strong></div>
    <div class="resumo__item"><span>Arquivados</span><strong>{{ $counts['arquivados'] }}</strong></div>
    <div class="resumo__item"><span>Categorias</span><strong>{{ $counts['categorias'] }}</strong></div>
</div>

@if(! $hasAny)
    <div class="vazio">
        <div class="vazio__icone" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1"/><path d="M10 12h4M10 16h4M10 8h4"/></svg>
        </div>
        <h2>Ainda não há procedimentos.</h2>
        @can('admin')<p>Precisa de pelo menos uma <a href="{{ route('admin.categorias.index') }}">categoria</a>.</p>@endcan
        <div class="accoes"><a class="btn btn--primario" href="{{ route('admin.procedimentos.create') }}">Criar o primeiro</a></div>
    </div>
@else
    <form class="filtros" method="get" action="{{ route('admin.procedimentos.index') }}" role="search" aria-label="Filtrar procedimentos">
        <div class="campo">
            <label for="q">Pesquisar</label>
            <div class="pesquisa">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" id="q" name="q" value="{{ $filters['q'] }}" autocomplete="off" placeholder="Título, problema, passo…">
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
            <a href="{{ route('admin.procedimentos.index') }}" class="btn btn--secundario">Limpar</a>
        </div>
    </form>

    @if($procedures->isEmpty())
        <div class="vazio">
            <h2>Sem resultados</h2>
            <p><a href="{{ route('admin.procedimentos.index') }}">Limpar filtros</a></p>
        </div>
    @else
        @if($filters['q'] || $filters['categoria'])
            <p class="meta">{{ $procedures->count() }} {{ $procedures->count() === 1 ? 'resultado' : 'resultados' }}</p>
        @endif
        <div class="tabela-wrap">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Ref.</th>
                        <th scope="col">Título</th>
                        <th scope="col">Categoria</th>
                        @can('admin')<th scope="col">Área</th>@endcan
                        <th scope="col">Passos</th>
                        <th scope="col">Alterado</th>
                        <th scope="col"><span class="visually-hidden">Acções</span></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($procedures as $p)
                    <tr>
                        <td><span class="etiqueta etiqueta--ref">{{ $p->reference }}</span></td>
                        <td>
                            <a href="{{ route('admin.procedimentos.edit', $p) }}"><strong>{{ $p->title }}</strong></a>
                            @if($p->is_archived) <span class="etiqueta etiqueta--arquivado">Arquivado</span> @endif
                        </td>
                        <td>{{ $p->category->name }}</td>
                        @can('admin')<td class="meta">{{ $p->area_label }}</td>@endcan
                        <td>{{ $p->steps_count }}</td>
                        <td class="meta" title="{{ $p->updated_at->format('d/m/Y H:i') }}@if($p->updated_by) · {{ $p->updated_by }}@endif">{{ $p->updated_at->format('d/m/Y') }}</td>
                        <td class="accoes">
                            @if($p->is_archived)
                                <form method="post" action="{{ route('admin.procedimentos.unarchive', $p) }}">
                                    @csrf
                                    <button type="submit" class="btn btn--secundario btn--pequeno">Desarquivar</button>
                                </form>
                            @else
                                <form method="post" action="{{ route('admin.procedimentos.archive', $p) }}"
                                      data-confirm="Arquivar «{{ $p->reference }} — {{ $p->title }}»? Deixa de aparecer na consulta, mas pode ser recuperado.">
                                    @csrf
                                    <button type="submit" class="btn btn--secundario btn--pequeno">Arquivar</button>
                                </form>
                            @endif
                            @can('admin')
                            <form method="post" action="{{ route('admin.procedimentos.destroy', $p) }}"
                                  data-confirm="Apagar definitivamente «{{ $p->reference }} — {{ $p->title }}»? Esta acção não pode ser anulada.">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn--perigo btn--pequeno">Apagar</button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endif
@endsection
