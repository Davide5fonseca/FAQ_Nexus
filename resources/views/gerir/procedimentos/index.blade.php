@extends('layouts.app')
@section('title', 'Procedimentos')

@section('caminho')
    <span class="actual">Procedimentos</span>
@endsection

@section('accoes')
    <a class="btn btn--primario" href="{{ route('gerir.procedimentos.create') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        Novo procedimento
    </a>
@endsection

@section('hero')
{{-- Sem cabeçalho à vista: a barra de topo e a barra lateral já dizem onde se
     está. O h1 fica, escondido, porque uma página sem cabeçalho principal
     deixa quem usa leitor de ecrã sem saber o que está a ler. --}}
<h1 class="visually-hidden">Procedimentos</h1>
@endsection

@section('content')
@if(! $hasAny)
    <div class="vazio">
        <div class="vazio__icone" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1"/><path d="M10 12h4M10 16h4M10 8h4"/></svg>
        </div>
        <h2>Ainda não há procedimentos.</h2>
        @can('admin')<p>Precisa de pelo menos uma <a href="{{ route('gerir.categorias.index') }}">categoria</a>.</p>@endcan
        <div class="accoes"><a class="btn btn--primario" href="{{ route('gerir.procedimentos.create') }}">Criar o primeiro</a></div>
    </div>
@else
    <form class="filtros" method="get" action="{{ route('gerir.procedimentos.index') }}" role="search" aria-label="Filtrar procedimentos">
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
            <a href="{{ route('gerir.procedimentos.index') }}" class="btn btn--secundario">Limpar</a>
        </div>
    </form>

    @if($procedures->isEmpty())
        <div class="vazio">
            <h2>Sem resultados</h2>
            <p><a href="{{ route('gerir.procedimentos.index') }}">Limpar filtros</a></p>
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
                            <a href="{{ route('gerir.procedimentos.edit', $p) }}"><strong>{{ $p->title }}</strong></a>
                        </td>
                        <td>{{ $p->category->name }}</td>
                        @can('admin')<td class="meta">{{ $p->area_label }}</td>@endcan
                        <td>{{ $p->steps_count }}</td>
                        <td class="meta" title="{{ $p->updated_at->format('d/m/Y H:i') }}@if($p->updated_by) · {{ $p->updated_by }}@endif">{{ $p->updated_at->format('d/m/Y') }}</td>
                        <td class="accoes">
                            <a class="btn btn--secundario btn--pequeno" href="{{ route('gerir.procedimentos.edit', $p) }}">Editar</a>
                            @can('admin')
                            <form method="post" action="{{ route('gerir.procedimentos.destroy', $p) }}"
                                  data-confirm="Eliminar «{{ $p->reference }} — {{ $p->title }}»? Esta acção não pode ser anulada.">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn--perigo btn--pequeno">Eliminar</button>
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
