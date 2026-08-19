@extends('layouts.app')
@section('title', $titulo)
@section('conteudo-class', 'conteudo--estreito')

@section('content')
<div class="vazio">
    <p class="meta">Erro {{ $codigo }}</p>
    <h2>{{ $titulo }}</h2>
    <p>{{ $mensagem }}</p>
    <a class="btn btn--primario" href="{{ route('consulta') }}">Ir para a consulta</a>
</div>
@endsection
