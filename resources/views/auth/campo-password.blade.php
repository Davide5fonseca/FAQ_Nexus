{{--
  Campo de palavra-passe com o "olhinho" para mostrar/ocultar, dentro do campo.
  Parâmetros: $id, $rotulo, $autocomplete e, opcionais, $autofocus e $minlength.
--}}
@php
    $autofocus = $autofocus ?? false;
    $minlength = $minlength ?? null;
@endphp
<div class="campo">
    <label for="{{ $id }}">{{ $rotulo }}</label>

    <div class="campo-password">
        <input type="password" id="{{ $id }}" name="{{ $id }}" required
               autocomplete="{{ $autocomplete }}"
               @if($minlength) minlength="{{ $minlength }}" @endif
               @if($autofocus) autofocus @endif
               @error($id) aria-invalid="true" aria-describedby="{{ $id }}-erro" @enderror>

        <button type="button" class="campo-password__olho"
                data-ver-password="{{ $id }}" aria-pressed="false"
                aria-label="Mostrar palavra-passe" title="Mostrar palavra-passe">
            {{-- Olho aberto: estado inicial (palavra-passe escondida) --}}
            <svg data-olho-mostrar viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            {{-- Olho riscado: quando a palavra-passe está visível --}}
            <svg data-olho-ocultar viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden>
                <path d="M10.6 6.1A8.9 8.9 0 0 1 12 6c6 0 9.5 6 9.5 6a15.6 15.6 0 0 1-3 3.6"/>
                <path d="M6.4 7.9A15.7 15.7 0 0 0 2.5 12S6 18 12 18a9.3 9.3 0 0 0 4-.9"/>
                <path d="M10 10a2.8 2.8 0 0 0 4 4"/>
                <path d="M3.5 3.5l17 17"/>
            </svg>
        </button>
    </div>

    @error($id)
        <p class="erro" id="{{ $id }}-erro" role="alert">{{ $message }}</p>
    @enderror
</div>
