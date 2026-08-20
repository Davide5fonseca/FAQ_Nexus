<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procedures', function (Blueprint $table) {
            // A que área pertence o procedimento: 'tecnica' ou 'producao'.
            // Cada pessoa só vê os da sua área; os administradores vêem tudo.
            $table->string('area', 20)->default('tecnica')->after('category_id');
            $table->index(['area', 'archived_at']);
        });

        // Todo o conteúdo já existente é da área técnica.
        DB::table('procedures')->update(['area' => 'tecnica']);
    }

    public function down(): void
    {
        Schema::table('procedures', function (Blueprint $table) {
            $table->dropIndex(['area', 'archived_at']);
            $table->dropColumn('area');
        });
    }
};
