<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Deixou de haver "arquivar": um procedimento existe ou é eliminado.
        if (Schema::hasColumn('procedures', 'archived_at')) {
            Schema::table('procedures', function (Blueprint $table) {
                $table->dropIndex(['area', 'archived_at']);
                $table->dropIndex(['archived_at', 'category_id']);
                $table->dropColumn('archived_at');
            });
            Schema::table('procedures', function (Blueprint $table) {
                $table->index(['area', 'category_id']);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('procedures', 'archived_at')) {
            Schema::table('procedures', function (Blueprint $table) {
                $table->dropIndex(['area', 'category_id']);
                $table->timestamp('archived_at')->nullable()->after('area');
                $table->index(['area', 'archived_at']);
                $table->index(['archived_at', 'category_id']);
            });
        }
    }
};
