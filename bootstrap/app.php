<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Quem ficar com a conta desactivada é posto fora no pedido seguinte,
        // mesmo que tenha entrado pelo "manter sessão iniciada".
        $middleware->web(append: [\App\Http\Middleware\GarantirContaActiva::class]);

        // Quem não tem sessão é enviado para a página de entrada.
        $middleware->redirectGuestsTo(fn () => route('login'));
        // Quem já tem sessão não precisa de ver a página de entrada.
        $middleware->redirectUsersTo(fn () => route('consulta'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
