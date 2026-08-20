{{--
  Modelo único dos emails da aplicação (convite e recuperação de palavra-passe).
  HTML em tabelas e estilos em linha, que é o que os clientes de email entendem.

  Nota sobre o botão: o Outlook (motor do Word) ignora "padding" dentro de <a>,
  por isso o espaçamento vai na célula <td>. Para os cantos arredondados usa-se
  VML, que só o Outlook lê; os restantes clientes usam o botão normal.

  Variáveis: $saudacao, $linhas[], $botaoTexto, $url, $notas[]
--}}
<!DOCTYPE html>
<html lang="pt" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ config('app.name') }}</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td, a { font-family: Arial, Helvetica, sans-serif !important; }
    </style>
    <![endif]-->
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; -webkit-font-smoothing:antialiased;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f1f5f9;">
    <tr>
        <td align="center" style="padding:32px 16px;">

            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:14px; overflow:hidden;">

                {{-- Faixa verde no topo --}}
                <tr>
                    <td height="6" style="height:6px; line-height:6px; font-size:0; background-color:#2FBC5E;">&nbsp;</td>
                </tr>

                {{-- Marca --}}
                <tr>
                    <td style="padding:30px 40px 0;">
                        <div style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:22px; font-weight:bold; color:#0F3D24; line-height:1.2;">{{ config('app.name') }}</div>
                        <div style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:11px; letter-spacing:2px; color:#8FB3A0; padding-top:5px;">NEXUS TECHNICAL SUITE</div>
                    </td>
                </tr>

                {{-- Saudação --}}
                <tr>
                    <td style="padding:26px 40px 0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:19px; font-weight:bold; color:#0F172A;">
                        {{ $saudacao }}
                    </td>
                </tr>

                {{-- Texto --}}
                @foreach($linhas as $linha)
                    <tr>
                        <td style="padding:14px 40px 0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:15px; line-height:24px; color:#374151;">
                            {!! $linha !!}
                        </td>
                    </tr>
                @endforeach

                {{-- Botão --}}
                <tr>
                    <td align="center" style="padding:30px 40px 6px;">
                        <!--[if mso]>
                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word"
                                     href="{{ $url }}" style="height:48px; v-text-anchor:middle; width:300px;"
                                     arcsize="21%" stroke="f" fillcolor="#2FBC5E">
                            <w:anchorlock/>
                            <center style="color:#081D10; font-family:Arial,sans-serif; font-size:15px; font-weight:bold;">{{ $botaoTexto }}</center>
                        </v:roundrect>
                        <![endif]-->
                        <!--[if !mso]><!-- -->
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
                            <tr>
                                <td align="center" bgcolor="#2FBC5E" style="background-color:#2FBC5E; border-radius:10px; padding:15px 34px;">
                                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                                       style="display:block; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:15px; font-weight:bold; line-height:18px; color:#081D10; text-decoration:none; white-space:nowrap;">{{ $botaoTexto }}</a>
                                </td>
                            </tr>
                        </table>
                        <!--<![endif]-->
                    </td>
                </tr>

                {{-- Notas --}}
                @foreach($notas as $nota)
                    <tr>
                        <td style="padding:{{ $loop->first ? '18px' : '6px' }} 40px 0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:13px; line-height:20px; color:#6b7280;">
                            {{ $nota }}
                        </td>
                    </tr>
                @endforeach

                {{-- Separador + endereço de recurso --}}
                <tr>
                    <td style="padding:26px 40px 34px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td height="1" style="height:1px; line-height:1px; font-size:0; background-color:#e3e8ef;">&nbsp;</td>
                            </tr>
                            <tr>
                                <td style="padding-top:16px; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:12px; line-height:18px; color:#9ca3af;">
                                    Se o botão não funcionar, copie e cole este endereço no seu browser:<br>
                                    <a href="{{ $url }}" style="color:#0F3D24; word-break:break-all;">{{ $url }}</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- Rodapé --}}
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%;">
                <tr>
                    <td align="center" style="padding:18px 16px 0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; font-size:11px; letter-spacing:1px; color:#9ca3af;">
                        NEXUS SOLUTIONS · USO INTERNO
                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>
</body>
</html>
