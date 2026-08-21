@extends('layouts.app')
@section('title', 'Imprimir')
@section('body-attrs', request()->query('auto') === '1' ? 'data-auto-imprimir="1"' : '')

@section('caminho')
    <a href="{{ route('consulta') }}">Consulta</a>
    <span class="topo__sep">/</span>
    <span class="actual">Imprimir</span>
@endsection

@section('content')
<div class="imp-barra no-print">
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('consulta') }}" class="btn btn--secundario">← Voltar</a>
    <span class="meta">{{ $procedures->count() }} {{ $procedures->count() === 1 ? 'página' : 'páginas' }} A4</span>
    <button type="button" class="btn btn--primario" onclick="window.print()">Imprimir</button>
</div>

@if($rules->isNotEmpty())
    <article class="pag-imp">
        <div class="imp-cab">
            <span>Nexus Solutions · {{ config('app.name') }}</span>
            <span>Impresso em {{ now()->format('d/m/Y') }}</span>
        </div>
        <section class="regras" aria-labelledby="regras-imp">
            <h2 id="regras-imp">Regras de segurança</h2>
            <ol>
                @foreach($rules as $rule)
                    <li>{{ $rule->content }}</li>
                @endforeach
            </ol>
        </section>
    </article>
@endif

@forelse($procedures as $p)
    <article class="pag-imp" aria-labelledby="imp-{{ $p->id }}">
        <div class="imp-cab">
            <span>Nexus Solutions · {{ config('app.name') }}</span>
            <span>{{ $p->reference }}</span>
        </div>
        <h1 class="imp-titulo" id="imp-{{ $p->id }}">{{ $p->title }}</h1>
        <div class="imp-meta">
            <span><strong>Referência:</strong> {{ $p->reference }}</span>
            <span><strong>Categoria:</strong> {{ $p->category->name }}</span>
        </div>

        @if(filled($p->problem))
        <section class="imp-sec">
            <h3>Problema / sintomas</h3>
            <p>{{ $p->problem }}</p>
        </section>
        @endif

        <section class="imp-sec">
            <h3>Solução — passos</h3>
            @if($p->steps->isEmpty())
                <p>—</p>
            @else
                <ol>
                    @foreach($p->steps as $step)
                        <li>{{ $step->content }}</li>
                    @endforeach
                </ol>
            @endif
        </section>

        <section class="imp-sec">
            <h3>O que registar no ticket</h3>
            <p>{{ filled($p->ticket_notes) ? $p->ticket_notes : '—' }}</p>
        </section>

        <section class="imp-sec">
            <h3>Quando escalar</h3>
            <p>{{ filled($p->escalation) ? $p->escalation : '—' }}</p>
        </section>

        <div class="imp-rodape">
            Última alteração: {{ $p->updated_at->format('d/m/Y H:i') }}@if($p->updated_by) por {{ $p->updated_by }}@endif
            · Criado em {{ $p->created_at->format('d/m/Y') }}
            · Impresso em {{ now()->format('d/m/Y') }}
        </div>
    </article>
@empty
    <div class="vazio no-print">
        <h2>Nada para imprimir.</h2>
        <p><a href="{{ route('consulta') }}">Voltar à consulta</a></p>
    </div>
@endforelse
@endsection
