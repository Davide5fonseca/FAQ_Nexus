<?php

namespace App\Http\Controllers;

use App\Models\Anexo;
use App\Models\Procedure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serve os anexos.
 *
 * É esta a razão de os ficheiros viverem fora da pasta pública: aqui
 * confirma-se, a cada pedido, que a pessoa vê aquele procedimento. Sem isto,
 * bastava saber o endereço de uma imagem para ver o conteúdo de uma área a
 * que não se pertence — e a separação por áreas passava a valer só para o
 * texto, não para as fotografias.
 */
class AnexoController extends Controller
{
    public function mostrar(Request $request, Procedure $procedure, Anexo $anexo): Response
    {
        // A mesma regra do resto da aplicação: cada um vê a sua área.
        abort_unless($procedure->visivelPor($request->user()), 403);

        // O anexo tem de ser mesmo deste procedimento — senão bastava trocar o
        // número na barra de endereço para ir buscar o anexo de outro.
        abort_unless($anexo->procedure_id === $procedure->id, 404);

        abort_unless($anexo->existeNoDisco(), 404);

        $descarregar = $request->boolean('descarregar');

        return response()->file(
            Storage::disk(Anexo::DISCO)->path($anexo->caminho()),
            [
                'Content-Type' => $anexo->tipo,
                // Sem isto, um browser podia decidir tratar o ficheiro como
                // outra coisa qualquer a partir do conteúdo.
                'X-Content-Type-Options' => 'nosniff',
                'Content-Disposition' => ($descarregar ? 'attachment' : 'inline')
                    .'; filename="'.addslashes($anexo->nome_original).'"',
                // São ficheiros de acesso reservado: não ficam em caches
                // partilhadas pelo caminho.
                'Cache-Control' => 'private, max-age=3600',
            ]
        );
    }
}
