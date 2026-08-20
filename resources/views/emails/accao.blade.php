{{--
  Modelo único dos emails da aplicação (convite e recuperação de palavra-passe).
  HTML em tabelas e estilos em linha, que é o que os clientes de email entendem.
  Variáveis: $saudacao, $linhas[], $botaoTexto, $url, $notas[]
--}}
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border:1px solid #e3e8ef; border-radius:14px; overflow:hidden;">

                    {{-- Faixa verde no topo --}}
                    <tr><td style="height:6px; line-height:6px; font-size:0; background-color:#2FBC5E;">&nbsp;</td></tr>

                    {{-- Marca --}}
                    <tr>
                        <td style="padding:28px 36px 6px;">
                            <div style="font-size:22px; font-weight:800; color:#0F3D24; line-height:1;">{{ config('app.name') }}</div>
                            <div style="font-size:11px; letter-spacing:2px; text-transform:uppercase; color:#8FB3A0; margin-top:4px;">Nexus Technical Suite</div>
                        </td>
                    </tr>

                    {{-- Corpo --}}
                    <tr>
                        <td style="padding:14px 36px 4px;">
                            <h1 style="margin:0 0 14px; font-size:20px; font-weight:600; color:#0F172A;">{{ $saudacao }}</h1>

                            @foreach($linhas as $linha)
                                <p style="margin:0 0 12px; font-size:15px; line-height:1.6; color:#374151;">{!! $linha !!}</p>
                            @endforeach

                            {{-- Botão --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px 0 0;">
                                <tr>
                                    <td align="center" bgcolor="#2FBC5E" style="border-radius:10px;">
                                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                                           style="display:inline-block; padding:14px 30px; font-size:15px; font-weight:700; color:#081D10; text-decoration:none; border-radius:10px;">{{ $botaoTexto }}</a>
                                    </td>
                                </tr>
                            </table>

                            @foreach($notas as $nota)
                                <p style="margin:{{ $loop->first ? '26px' : '0' }} 0 6px; font-size:13px; line-height:1.6; color:#6b7280;">{{ $nota }}</p>
                            @endforeach
                        </td>
                    </tr>

                    {{-- Separador + endereço de recurso --}}
                    <tr>
                        <td style="padding:22px 36px 30px;">
                            <div style="border-top:1px solid #e3e8ef; padding-top:16px;">
                                <p style="margin:0 0 6px; font-size:12px; line-height:1.5; color:#9ca3af;">Se o botão não funcionar, copie e cole este endereço no seu browser:</p>
                                <a href="{{ $url }}" style="font-size:12px; color:#0F3D24; word-break:break-all;">{{ $url }}</a>
                            </div>
                        </td>
                    </tr>
                </table>

                <p style="margin:18px 0 0; font-size:11px; letter-spacing:1px; color:#9ca3af;">NEXUS SOLUTIONS · USO INTERNO</p>
            </td>
        </tr>
    </table>
</body>
</html>
