<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anexos de um procedimento: fotografias do equipamento, imagens de ecrã,
 * folhas de instruções em PDF.
 *
 * O ficheiro em si NÃO fica na pasta pública: fica em `storage/app/private`,
 * e é servido por uma rota que confirma primeiro quem está a pedir e a que
 * área pertence. Se ficasse na pasta pública, bastaria saber o endereço para
 * ver a imagem de um procedimento de outra área — e a separação por áreas
 * deixava de valer para as imagens.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete();

            // Nome do ficheiro gravado no disco. Nunca é o nome original: é
            // gerado por nós, para ninguém conseguir escrever fora da pasta
            // nem adivinhar o caminho de um anexo alheio.
            $table->string('ficheiro');

            // O nome que a pessoa vê, e que vai no download.
            $table->string('nome_original');

            $table->string('tipo', 100);        // image/png, application/pdf, ...
            $table->unsignedInteger('tamanho'); // bytes
            $table->string('legenda', 200)->nullable();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->string('criado_por')->nullable();
            $table->timestamps();

            $table->index(['procedure_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anexos');
    }
};
