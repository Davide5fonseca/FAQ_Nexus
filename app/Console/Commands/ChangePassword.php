<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ChangePassword extends Command
{
    protected $signature = 'app:alterar-password
        {--email= : Email da conta (se omitido e só houver uma conta, usa essa)}
        {--password= : Nova palavra-passe (mínimo 10 caracteres). Se omitida, é pedida de forma oculta.}';

    protected $description = 'Altera a palavra-passe de uma conta de administrador e termina as sessões abertas.';

    public function handle(): int
    {
        $email = $this->option('email');

        if (! $email && User::count() === 1) {
            $email = User::first()->email;
        }

        $email = $email ?: $this->ask('Email da conta');
        $user = User::where('email', mb_strtolower(trim((string) $email)))->first();

        if (! $user) {
            $this->error("Não existe nenhuma conta com o email {$email}.");

            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->secret('Nova palavra-passe (mínimo 10 caracteres)');

        if (mb_strlen((string) $password) < 10) {
            $this->error('A palavra-passe tem de ter pelo menos 10 caracteres.');

            return self::FAILURE;
        }

        $user->forceFill(['password' => Hash::make($password)])->save();

        // Termina todas as sessões abertas desta conta.
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $this->info("Palavra-passe alterada para {$user->email}. As sessões abertas foram terminadas.");

        return self::SUCCESS;
    }
}
