<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email de convite: a conta foi criada pelo administrador; a pessoa define a palavra-passe. */
class DefinirPalavraPasse extends Notification
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $dias = (int) round(config('auth.passwords.users.expire') / 1440);
        $app = config('app.name');

        return (new MailMessage)
            ->subject("A sua conta na {$app} — defina a palavra-passe")
            ->view(['emails.accao', 'emails.accao-texto'], [
                'saudacao' => 'Olá, '.$notifiable->name.',',
                'linhas' => [
                    "Foi criada uma conta para si na <strong style=\"color:#0F172A;\">{$app}</strong>, da Nexus Solutions.",
                    'Para começar a usar, defina a sua palavra-passe no botão abaixo.',
                ],
                'botaoTexto' => 'Definir palavra-passe',
                'url' => $url,
                'notas' => [
                    "Este link é válido durante {$dias} ".($dias === 1 ? 'dia' : 'dias').'.',
                    'Se não esperava este email, ignore-o.',
                ],
            ]);
    }
}
