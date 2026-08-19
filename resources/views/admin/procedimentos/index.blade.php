@extends('layouts.app')
@section('title', 'Administração · Procedimentos')

@section('content')
<div class="cabecalho-pagina">
    <h1>Procedimentos</h1>
    <div class="accoes">
        <a class="btn btn--primario" href="{{ route('admin.procedimentos.create') }}">Novo procedimento</a>
    </div>
</div>

@if(! $hasAny)
    <div class="vazio">
        <h2>Ainda não há procedimentos.</h2>
        <p>Crie o primeiro.@can('admin') Se ainda não tiver categorias, pode criá-las em <a href="{{ route('admin.categorias.index') }}">Categorias</a>.@endcan</p>
        <a class="btn btn--primario" href="{{ route('admin.procedimentos.create') }}">Criar o primeiro</a>
    </div>
@else
    <form class="filtros filtros--admin" method="get" action="{{ route('admin.procedimentos.index') }}" role="search" aria-label="Filtrar procedimentos">
        <div class="campo">
            <label for="q">Pesquisar</label>
            <input type="search" id="q" name="q" value="{{ $filters['q'] }}" autocomplete="off">
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
        <div class="campo">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="activos" @selected($filters['estado'] === 'activos')>Activos</option>
                <option value="arquivados" @selected($filters['estado'] === 'arquivados')>Arquivados</option>
                <option value="todos" @selected($filters['estado'] === 'todos')>Todos</option>
            </select>
        </div>
        <div class="filtros__accoes">
            <button type="submit" class="btn btn--escuro">Filtrar</button>
            <a href="{{ route('admin.procedimentos.index') }}" class="btn btn--secundario">Limpar</a>
        </div>
    </form>

    @if($procedures->isEmpty())
        <div class="vazio">
            <h2>Nenhum procedimento corresponde aos filtros.</h2>
            <p><a href="{{ route('admin.procedimentos.index') }}">Limpar filtros</a></p>
        </div>
    @else
        <p class="meta">{{ $procedures->count() }} {{ $procedures->count() === 1 ? 'procedimento' : 'procedimentos' }}</p>
        <div class="tabela-wrap">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Ref.</th>
                        <th scope="col">Título</th>
                        <th scope="col">Categoria</th>
                        <th scope="col">Nível</th>
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
                        <td><span class="etiqueta etiqueta--nivel-{{ $p->level }}">Nível {{ $p->level }}</span></td>
                        <td>{{ $p->steps_count }}</td>
                        <td class="meta">{{ $p->updated_at->format('d/m/Y H:i') }}<br>{{ $p->updated_by }}</td>
                        <td class="accoes">
                            <a class="btn btn--secundario btn--pequeno" href="{{ route('admin.procedimentos.edit', $p) }}">Editar</a>
                            <form method="post" action="{{ route('admin.procedimentos.duplicate', $p) }}">
                                @csrf
                                <button type="submit" class="btn btn--secundario btn--pequeno">Duplicar</button>
                            </form>
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
