<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'app:criar-admin
        {--nome= : Nome a mostrar (ex.: "João Silva")}
        {--email= : Email de entrada}
        {--password= : Palavra-passe (mínimo 10 caracteres). Se omitida, é pedida de forma oculta.}';

    protected $description = 'Cria (ou actualiza) a conta de administrador da aplicação.';

    public function handle(): int
    {
        $name = $this->option('nome') ?: $this->ask('Nome do administrador');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Palavra-passe (mínimo 10 caracteres)');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:190'],
                'password' => ['required', 'string', 'min:10'],
            ],
            [],
            ['name' => 'nome', 'email' => 'email', 'password' => 'palavra-passe']
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => mb_strtolower(trim($email))],
            ['name' => trim($name), 'password' => Hash::make($password)]
        );

        $this->info($user->wasRecentlyCreated
            ? "Conta de administrador criada: {$user->email}"
            : "Conta de administrador actualizada: {$user->email}");

        return self::SUCCESS;
    }
}
