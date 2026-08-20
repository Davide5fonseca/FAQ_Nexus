@extends('layouts.app')
@section('title', 'Administração · Utilizadores')

@section('content')
<div class="cabecalho-pagina">
    <h1>Utilizadores</h1>
    <div class="accoes">
        <a class="btn btn--primario" href="{{ route('admin.utilizadores.create') }}">Nova conta</a>
    </div>
</div>

<p class="meta">
    <strong>Administrador</strong>: gere tudo (procedimentos, categorias, regras, contas).
    <strong>Editor</strong>: cria, edita, duplica e arquiva procedimentos — é o perfil para quem, na área técnica ou na produção, carrega problemas e soluções.
    <strong>Leitor</strong>: só consulta e imprime; não altera nada nem vê a administração.
</p>

<div class="tabela-wrap">
    <table>
        <thead>
            <tr>
                <th scope="col">Nome</th>
                <th scope="col">Email</th>
                <th scope="col">Área</th>
                <th scope="col">Perfil</th>
                <th scope="col">Estado</th>
                <th scope="col"><span class="visually-hidden">Acções</span></th>
            </tr>
        </thead>
        <tbody>
        @foreach($users as $u)
            <tr>
                <td><a href="{{ route('admin.utilizadores.edit', $u) }}"><strong>{{ $u->name }}</strong></a>@if(auth()->user()->is($u)) <span class="meta">(eu)</span>@endif</td>
                <td>{{ $u->email }}</td>
                <td>{{ $u->area_label ?? '—' }}</td>
                <td><span class="etiqueta {{ $u->is_admin ? 'etiqueta--perfil-admin' : '' }}">{{ $u->role_label }}</span></td>
                <td>@if($u->active) Activa @else <span class="etiqueta etiqueta--arquivado">Desactivada</span> @endif</td>
                <td class="accoes">
                    <a class="btn btn--secundario btn--pequeno" href="{{ route('admin.utilizadores.edit', $u) }}">Editar</a>
                    <form method="post" action="{{ route('admin.utilizadores.convite', $u) }}"
                          data-confirm="Enviar a {{ $u->name }} ({{ $u->email }}) um email com o link para definir nova palavra-passe?">
                        @csrf
                        <button type="submit" class="btn btn--secundario btn--pequeno">Enviar convite</button>
                    </form>
                    @unless(auth()->user()->is($u))
                        <form method="post" action="{{ route('admin.utilizadores.destroy', $u) }}" data-confirm="Apagar a conta de «{{ $u->name }}»? Os procedimentos que criou mantêm-se.">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn--perigo btn--pequeno">Apagar</button>
                        </form>
                    @endunless
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
