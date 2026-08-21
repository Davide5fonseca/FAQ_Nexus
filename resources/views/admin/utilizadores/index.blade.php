@extends('layouts.app')
@section('title', 'Perfis')

@section('caminho')
    <span class="actual">Perfis</span>
@endsection

@section('content')
<div class="cabecalho-pagina">
    <h1>Perfis</h1>
</div>

<p class="meta">
    As contas e o acesso a esta aplicação são geridos no
    <a href="{{ config('app.portal_url') }}/gestao/utilizadores">portal</a>.
    Aqui decide-se o que cada pessoa faz cá dentro.
</p>

@if($utilizadores->isEmpty())
    <div class="vazio">
        <h2>Ninguém tem acesso a esta aplicação</h2>
        <p>Atribua o acesso no <a href="{{ config('app.portal_url') }}/gestao/utilizadores">portal</a>.</p>
    </div>
@else
    <div class="tabela-wrap">
        <table>
            <thead>
                <tr>
                    <th scope="col">Nome</th>
                    <th scope="col">Email</th>
                    <th scope="col">Área</th>
                    <th scope="col">Perfil</th>
                    <th scope="col"><span class="visually-hidden">Acções</span></th>
                </tr>
            </thead>
            <tbody>
            @foreach($utilizadores as $u)
                @php $perfil = $perfis->get($u->id); @endphp
                <tr>
                    <td>
                        <a href="{{ route('admin.utilizadores.edit', $u->id) }}"><strong>{{ $u->name }}</strong></a>
                        @if(auth()->id() === $u->id) <span class="meta">(eu)</span>@endif
                        @unless($u->active) <span class="etiqueta etiqueta--inactiva">Desactivada</span> @endunless
                    </td>
                    <td class="meta">{{ $u->email }}</td>
                    <td>{{ $perfil?->area ? (\App\Models\User::AREAS[$perfil->area] ?? $perfil->area) : '—' }}</td>
                    <td>
                        <span class="etiqueta {{ ($perfil?->papel ?? 'leitor') === 'admin' ? 'etiqueta--perfil-admin' : '' }}">
                            {{ \App\Models\User::ROLES[$perfil?->papel ?? 'leitor'] }}
                        </span>
                    </td>
                    <td class="accoes">
                        <a class="btn btn--secundario btn--pequeno" href="{{ route('admin.utilizadores.edit', $u->id) }}">Editar perfil</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
