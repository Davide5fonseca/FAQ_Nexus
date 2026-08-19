<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('pt_PT');

        // Só administradores gerem utilizadores, categorias, regras e apagam procedimentos.
        Gate::define('admin', fn ($user) => $user->role === 'admin');

        // Máximo de 5 tentativas de entrada por minuto, por email + IP.
        RateLimiter::for('login', function (Request $request) {
            $key = mb_strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key)->response(function () {
                return back()
                    ->withInput(request()->only('email'))
                    ->withErrors(['email' => 'Demasiadas tentativas. Aguarde um minuto e tente novamente.']);
            });
        });
    }
}
