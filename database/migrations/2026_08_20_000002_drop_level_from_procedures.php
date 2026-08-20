<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Deixou de haver "nível de intervenção".
        if (Schema::hasColumn('procedures', 'level')) {
            Schema::table('procedures', function (Blueprint $table) {
                $table->dropIndex(['archived_at', 'category_id', 'level']);
                $table->dropColumn('level');
            });
            Schema::table('procedures', function (Blueprint $table) {
                $table->index(['archived_at', 'category_id']);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('procedures', 'level')) {
            Schema::table('procedures', function (Blueprint $table) {
                $table->dropIndex(['archived_at', 'category_id']);
                $table->unsignedTinyInteger('level')->default(1)->after('category_id');
                $table->index(['archived_at', 'category_id', 'level']);
            });
        }
    }
};
