{{ config('app.name') }}
{{ str_repeat('=', mb_strlen(config('app.name'))) }}

{{ $saudacao }}

@foreach($linhas as $linha)
{{ strip_tags($linha) }}

@endforeach
{{ $botaoTexto }}:
{{ $url }}

@foreach($notas as $nota)
{{ $nota }}

@endforeach
--
Nexus Solutions · Uso interno
