<?php

namespace App\Providers;

use App\Mail\Transport\GraphTransport;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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

        // Em produção, todos os links gerados (emails incluídos) usam o endereço público
        // configurado em APP_URL (com a porta externa correcta).
        if ($this->app->isProduction() && config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }

        // Transporte de email 'graph' (Microsoft Graph, app-only). O closure só corre
        // quando o mailer 'graph' é usado. Credenciais em config/services.php (via .env).
        Mail::extend('graph', function () {
            $c = config('services.microsoft_graph');

            return new GraphTransport($c['tenant_id'], $c['client_id'], $c['client_secret'], $c['sender']);
        });

        // Email de recuperação de palavra-passe em português.
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            return (new MailMessage)
                ->subject('Recuperação de palavra-passe — Base de Procedimentos Técnicos')
                ->greeting('Olá, '.$notifiable->name.',')
                ->line('Recebemos um pedido para definir uma nova palavra-passe para a sua conta.')
                ->action('Definir nova palavra-passe', $url)
                ->line('Este link expira em '.(int) round(config('auth.passwords.users.expire') / 1440).' dias.')
                ->line('Se não foi você que fez este pedido, ignore este email.')
                ->salutation('Cumprimentos, Nexus Solutions');
        });

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
