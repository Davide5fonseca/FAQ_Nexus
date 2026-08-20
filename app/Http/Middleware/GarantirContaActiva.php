<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Termina a sessão de quem tenha a conta desactivada.
 *
 * A verificação na entrada não chega: quem tenha marcado "manter sessão iniciada"
 * volta a entrar pelo cookie, e esse caminho não passa pela validação do login.
 * Assim, a desactivação faz efeito no pedido seguinte, seja qual for a via.
 */
class GarantirContaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $utilizador = $request->user();

        if ($utilizador && ! $utilizador->active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'A sua conta foi desactivada. Contacte um administrador.']);
        }

        return $next($request);
    }
}
