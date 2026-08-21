@extends('layouts.app')
@php $editing = $procedure->exists; @endphp
@section('title', $editing ? 'Editar '.$procedure->reference : 'Novo procedimento')
@section('largura', 'largura--media')

@section('caminho')
    <a href="{{ route('admin.procedimentos.index') }}">Procedimentos</a>
    <span class="topo__sep">/</span>
    <span class="actual">{{ $editing ? $procedure->reference : 'Novo' }}</span>
@endsection

@section('content')
<div class="cabecalho-pagina">
    <h1>
        @if($editing)
            Editar <span class="etiqueta etiqueta--ref">{{ $procedure->reference }}</span>
        @else
            Novo procedimento
        @endif
    </h1>
    <div class="accoes">
        <a class="btn btn--secundario" href="{{ route('admin.procedimentos.index') }}">← Voltar à lista</a>
        @if($editing)
            <a class="btn btn--secundario" href="{{ route('consulta') }}#proc-{{ $procedure->reference_number }}">Ver na consulta</a>
        @endif
    </div>
</div>

@if($categories->isEmpty())
    <div class="alerta alerta--aviso" role="alert">
        <span aria-hidden="true">!</span>
        <div>Ainda não existem categorias. @can('admin')<a href="{{ route('admin.categorias.index') }}">Crie pelo menos uma categoria</a> antes de guardar o procedimento.@else Peça a um administrador para criar categorias antes de guardar o procedimento.@endcan</div>
    </div>
@endif

<form method="post" action="{{ $editing ? route('admin.procedimentos.update', $procedure) : route('admin.procedimentos.store') }}" class="cartao" novalidate>
    @csrf
    @if($editing) @method('PUT') @endif

    @php $steps = old('steps', $steps) ?: ['']; @endphp

    <div class="campo">
        <label for="title">Título</label>
        <input type="text" id="title" name="title" value="{{ old('title', $procedure->title) }}" required maxlength="200" autofocus
               @error('title') aria-invalid="true" aria-describedby="title-erro" @enderror>
        @error('title')<p class="erro" id="title-erro">{{ $message }}</p>@enderror
    </div>

    <div class="campo">
        <label for="problem">Problema / sintomas</label>
        <textarea id="problem" name="problem" rows="3" maxlength="5000" placeholder="O que se observa: erro, comportamento, em que equipamento…"
                  @error('problem') aria-invalid="true" aria-describedby="problem-erro" @enderror>{{ old('problem', $procedure->problem) }}</textarea>
        @error('problem')<p class="erro" id="problem-erro">{{ $message }}</p>@enderror
    </div>

    <div class="campo">
        <label for="category_id">Categoria</label>
        <select id="category_id" name="category_id" required @error('category_id') aria-invalid="true" aria-describedby="category-erro" @enderror>
            <option value="">— Escolher —</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected((int) old('category_id', $procedure->category_id) === $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
        @error('category_id')<p class="erro" id="category-erro">{{ $message }}</p>@enderror
        @can('admin')<p class="ajuda"><a href="{{ route('admin.categorias.index') }}">Gerir categorias</a></p>@endcan
    </div>

    @can('admin')
        <fieldset class="campo">
            <legend class="legenda">Área</legend>
            <div class="radios">
                @foreach(\App\Models\User::AREAS as $key => $label)
                    <label>
                        <input type="radio" name="area" value="{{ $key }}"
                               @checked(old('area', $procedure->area ?? auth()->user()->area) === $key)>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            @error('area')<p class="erro">{{ $message }}</p>@enderror
            <p class="ajuda">Só quem pertence a esta área (e os administradores) verá o procedimento.</p>
        </fieldset>
    @else
        <div class="campo">
            <span class="legenda">Área</span>
            <p class="meta" style="margin:0">{{ auth()->user()->area_label }} — só a sua área verá este procedimento.</p>
        </div>
    @endcan

    <fieldset class="campo" data-passos>
        <legend class="legenda">Passos, por ordem</legend>
        @error('steps')<p class="erro">{{ $message }}</p>@enderror
        <p class="ajuda" style="margin-bottom:.5rem">Arraste ⠿ ou use ↑ ↓ para reordenar.</p>
        <p class="visually-hidden" aria-live="polite" data-aviso-passos></p>

        <ol class="passos">
            @foreach($steps as $i => $content)
                <li class="passo">
                    <span class="passo__num" aria-hidden="true">{{ $i + 1 }}.</span>
                    <textarea name="steps[]" rows="2" aria-label="Passo {{ $i + 1 }}" maxlength="5000">{{ $content }}</textarea>
                    <span class="passo__accoes">
                        <button type="button" class="btn btn--secundario btn--icone passo__pega" title="Arrastar para reordenar" aria-hidden="true" tabindex="-1">⠿</button>
                        <button type="button" class="btn btn--secundario btn--icone" data-subir aria-label="Mover para cima" title="Mover para cima">↑</button>
                        <button type="button" class="btn btn--secundario btn--icone" data-descer aria-label="Mover para baixo" title="Mover para baixo">↓</button>
                        <button type="button" class="btn btn--perigo btn--icone" data-remover aria-label="Remover passo" title="Remover passo">×</button>
                    </span>
                </li>
            @endforeach
            <noscript>
                {{-- Sem JavaScript: há sempre um campo extra para acrescentar um passo. --}}
                <li class="passo">
                    <span class="passo__num" aria-hidden="true">+</span>
                    <textarea name="steps[]" rows="2" aria-label="Novo passo" maxlength="5000" placeholder="Novo passo (opcional)"></textarea>
                    <span class="passo__accoes"></span>
                </li>
            </noscript>
        </ol>

        <template>
            <li class="passo">
                <span class="passo__num" aria-hidden="true"></span>
                <textarea name="steps[]" rows="2" maxlength="5000"></textarea>
                <span class="passo__accoes">
                    <button type="button" class="btn btn--secundario btn--icone passo__pega" title="Arrastar para reordenar" aria-hidden="true" tabindex="-1">⠿</button>
                    <button type="button" class="btn btn--secundario btn--icone" data-subir aria-label="Mover para cima" title="Mover para cima">↑</button>
                    <button type="button" class="btn btn--secundario btn--icone" data-descer aria-label="Mover para baixo" title="Mover para baixo">↓</button>
                    <button type="button" class="btn btn--perigo btn--icone" data-remover aria-label="Remover passo" title="Remover passo">×</button>
                </span>
            </li>
        </template>

        <button type="button" class="btn btn--secundario" data-adicionar-passo>+ Adicionar passo</button>
        <noscript><p class="ajuda">Sem JavaScript: para acrescentar mais do que um passo de cada vez, guarde e volte a editar.</p></noscript>
    </fieldset>

    <div class="campo">
        <label for="ticket_notes">O que registar no ticket</label>
        <textarea id="ticket_notes" name="ticket_notes" rows="4" maxlength="5000"
                  @error('ticket_notes') aria-invalid="true" aria-describedby="ticket-erro" @enderror>{{ old('ticket_notes', $procedure->ticket_notes) }}</textarea>
        @error('ticket_notes')<p class="erro" id="ticket-erro">{{ $message }}</p>@enderror
    </div>

    <div class="campo">
        <label for="escalation">Quando escalar</label>
        <textarea id="escalation" name="escalation" rows="4" maxlength="5000"
                  @error('escalation') aria-invalid="true" aria-describedby="escalation-erro" @enderror>{{ old('escalation', $procedure->escalation) }}</textarea>
        @error('escalation')<p class="erro" id="escalation-erro">{{ $message }}</p>@enderror
    </div>

    @if($editing)
        <p class="meta">
            Criado em {{ $procedure->created_at->format('d/m/Y H:i') }}@if($procedure->created_by) por {{ $procedure->created_by }}@endif
            · Última alteração em {{ $procedure->updated_at->format('d/m/Y H:i') }}@if($procedure->updated_by) por {{ $procedure->updated_by }}@endif
        </p>
    @endif

    <div class="accoes-form">
        <button type="submit" class="btn btn--primario">{{ $editing ? 'Guardar alterações' : 'Criar procedimento' }}</button>
        <a class="btn btn--secundario" href="{{ route('admin.procedimentos.index') }}">Cancelar</a>
    </div>
</form>

@if($editing)
    <div class="cartao">
        <h2>Outras acções</h2>
        <div class="accoes-form" style="border:0;padding:0;margin:0">
            @can('admin')
            <form method="post" action="{{ route('admin.procedimentos.destroy', $procedure) }}"
                  data-confirm="Eliminar «{{ $procedure->reference }} — {{ $procedure->title }}»? Esta acção não pode ser anulada.">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn--perigo">Eliminar</button>
            </form>
            @endcan
        </div>
    </div>
@endif
@endsection
