<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O perfil de cada pessoa NESTA aplicação: administrador/editor/leitor e a área.
 *
 * A identidade passa a vir do portal (tabela `utilizadores`, partilhada), mas o
 * que cada um pode fazer aqui dentro continua a ser decidido aqui — por isso
 * esta tabela vive na base de dados desta aplicação e aponta para a pessoa
 * apenas pelo número.
 *
 * Os perfis já existentes são transferidos da antiga tabela `users`, emparelhados
 * pelo email. A tabela antiga não é apagada: fica como rede de segurança.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfis', function (Blueprint $table) {
            $table->id();
            // Número da pessoa na tabela `utilizadores` (outra base de dados,
            // por isso sem chave estrangeira).
            $table->unsignedBigInteger('utilizador_id')->unique();
            $table->string('papel', 20)->default('leitor');
            $table->string('area', 20)->nullable();
            $table->timestamps();
        });

        $this->transferirPerfisExistentes();
    }

    /** Passa os perfis da tabela antiga para a nova, emparelhando pelo email. */
    private function transferirPerfisExistentes(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        foreach (DB::table('users')->get() as $antigo) {
            $pessoa = User::query()->where('email', mb_strtolower($antigo->email))->first();

            if (! $pessoa) {
                // Ninguém com este email na lista partilhada: não se inventa
                // uma conta; fica registado no log da migração.
                echo "  aviso: {$antigo->email} não existe na lista partilhada — perfil não transferido\n";

                continue;
            }

            DB::table('perfis')->updateOrInsert(
                ['utilizador_id' => $pessoa->id],
                [
                    'papel' => $antigo->role ?? 'leitor',
                    'area' => $antigo->area,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('perfis');
    }
};
