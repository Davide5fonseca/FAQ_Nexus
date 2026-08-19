@extends('layouts.app')
@section('title', 'Administração · Regras de segurança')

@section('content')
<div class="cabecalho-pagina">
    <h1>Regras de segurança</h1>
</div>

<p class="meta">Estas regras aparecem no topo da página de consulta, pela ordem indicada aqui, e na primeira página ao imprimir a lista.</p>

<div class="cartao">
    <h2>Nova regra</h2>
    <form method="post" action="{{ route('admin.regras.store') }}" novalidate>
        @csrf
        <div class="campo">
            <label for="content">Texto da regra</label>
            <textarea id="content" name="content" rows="2" required maxlength="2000"
                      @error('content') aria-invalid="true" aria-describedby="content-erro" @enderror>{{ old('content') }}</textarea>
            @error('content')<p class="erro" id="content-erro">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn btn--primario">Adicionar</button>
    </form>
</div>

@if($rules->isEmpty())
    <div class="vazio">
        <h2>Ainda não há regras de segurança.</h2>
        <p>Adicione a primeira no campo acima. Enquanto não houver regras, a secção não aparece na consulta.</p>
    </div>
@else
    <h2>{{ $rules->count() }} {{ $rules->count() === 1 ? 'regra' : 'regras' }}</h2>
    <ul class="lista-edit">
        @foreach($rules as $i => $rule)
            <li>
                <span class="num" aria-hidden="true">{{ $i + 1 }}.</span>
                <span class="ordem">
                    <form method="post" action="{{ route('admin.regras.move', $rule) }}">
                        @csrf <input type="hidden" name="direction" value="up">
                        <button type="submit" class="btn btn--secundario btn--icone" aria-label="Mover regra {{ $i + 1 }} para cima" title="Mover para cima" @if($i === 0) disabled @endif>↑</button>
                    </form>
                    <form method="post" action="{{ route('admin.regras.move', $rule) }}">
                        @csrf <input type="hidden" name="direction" value="down">
                        <button type="submit" class="btn btn--secundario btn--icone" aria-label="Mover regra {{ $i + 1 }} para baixo" title="Mover para baixo" @if($loop->last) disabled @endif>↓</button>
                    </form>
                </span>
                <form method="post" action="{{ route('admin.regras.update', $rule) }}" class="editar" novalidate>
                    @csrf @method('PUT')
                    <label for="rule-{{ $rule->id }}" class="visually-hidden">Texto da regra {{ $i + 1 }}</label>
                    <textarea id="rule-{{ $rule->id }}" name="content" rows="2" required maxlength="2000">{{ $rule->content }}</textarea>
                    <button type="submit" class="btn btn--secundario">Guardar</button>
                </form>
                <form method="post" action="{{ route('admin.regras.destroy', $rule) }}" data-confirm="Apagar esta regra de segurança?">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn--perigo">Apagar</button>
                </form>
            </li>
        @endforeach
    </ul>
@endif
@endsection
