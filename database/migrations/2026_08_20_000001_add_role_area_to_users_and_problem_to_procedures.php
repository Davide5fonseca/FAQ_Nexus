<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // admin = gere tudo; editor = cria e edita procedimentos
            $table->string('role', 20)->default('editor')->after('password');
            // tecnica | producao
            $table->string('area', 20)->nullable()->after('role');
            $table->boolean('active')->default(true)->after('area');
        });

        // Contas já existentes passam a administradores (área técnica).
        \Illuminate\Support\Facades\DB::table('users')->update(['role' => 'admin', 'area' => 'tecnica', 'active' => true]);

        Schema::table('procedures', function (Blueprint $table) {
            $table->text('problem')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('procedures', fn (Blueprint $t) => $t->dropColumn('problem'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'area', 'active']));
    }
};
