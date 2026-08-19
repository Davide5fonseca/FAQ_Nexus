<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contador para gerar referências PROC-01, PROC-02... que nunca se repetem,
        // mesmo depois de apagar procedimentos.
        Schema::create('counters', function (Blueprint $table) {
            $table->string('name', 50)->primary();
            $table->unsignedInteger('value')->default(0);
        });

        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('reference_number')->unique();
            $table->string('title', 200);
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->unsignedTinyInteger('level');
            $table->text('ticket_notes')->nullable();
            $table->text('escalation')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->string('created_by', 120)->nullable();
            $table->string('updated_by', 120)->nullable();
            $table->timestamps();

            $table->index(['archived_at', 'category_id', 'level']);
        });

        Schema::create('procedure_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_id')->constrained('procedures')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->text('content');

            $table->index(['procedure_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_steps');
        Schema::dropIfExists('procedures');
        Schema::dropIfExists('counters');
    }
};
