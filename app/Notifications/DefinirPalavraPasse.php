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

        return (new MailMessage)
            ->subject('A sua conta na '.config('app.name').' — defina a palavra-passe')
            ->greeting('Olá, '.$notifiable->name.',')
            ->line('Foi criada uma conta para si na '.config('app.name').', da Nexus Solutions.')
            ->line('Para começar a usar, defina a sua palavra-passe através do botão abaixo.')
            ->action('Definir palavra-passe', $url)
            ->line("Este link é válido durante {$dias} ".($dias === 1 ? 'dia' : 'dias').'. Depois disso, peça um novo em "Esqueci-me da palavra-passe" na página de entrada.')
            ->line('Se não esperava este email, ignore-o.')
            ->salutation('Cumprimentos, Nexus Solutions');
    }
}
