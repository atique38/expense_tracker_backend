<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove legacy soft delete columns and purge already trashed rows.
     */
    public function up(): void
    {
        foreach (['transactions', 'budgets', 'categories', 'accounts'] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'deleted_at')) {
                continue;
            }

            DB::table($tableName)
                ->whereNotNull('deleted_at')
                ->delete();

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }

    /**
     * Restore the soft delete column if the migration is rolled back.
     */
    public function down(): void
    {
        foreach (['accounts', 'categories', 'budgets', 'transactions'] as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'deleted_at')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }
};
