<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Só para os testes.
 *
 * As tabelas `utilizadores`, `aplicacoes` e `acessos` pertencem ao portal e
 * vivem noutra base de dados. Em produção esta migração não faz nada — só cria
 * as tabelas quando se está a correr os testes, onde as duas ligações apontam
 * ao mesmo sítio e não há portal nenhum a alimentá-las.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! app()->environment('testing')) {
            return;
        }

        if (! Schema::hasTable('utilizadores')) {
            Schema::create('utilizadores', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('papel', 20)->default('tecnico');
                $table->boolean('ativo')->default(true);
                $table->timestamp('password_alterada_em')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('aplicacoes')) {
            Schema::create('aplicacoes', function (Blueprint $table) {
                $table->id();
                $table->string('chave', 40)->unique();
                $table->string('nome', 80);
                $table->string('url', 255)->default('');
                $table->boolean('activa')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('acessos')) {
            Schema::create('acessos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('utilizador_id');
                $table->unsignedBigInteger('aplicacao_id');
                // O papel e a área de cada pessoa em cada aplicação vivem aqui:
                // é o portal que os decide e as aplicações que obedecem.
                $table->string('papel', 20)->nullable();
                $table->string('contexto', 40)->nullable();
                $table->timestamps();
                $table->unique(['utilizador_id', 'aplicacao_id']);
            });
        }
    }

    public function down(): void
    {
        // Não se apagam tabelas que pertencem a outra aplicação.
    }
};
