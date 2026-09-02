<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'users',
            'admins',
            'agents',
            'players',
            'employees',
            'financial_records',
            'deals',
            'contracts',
            'club_contracts',
            'training_programs',
            'player_physical_reports',
            'nutrition_programs',
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'deleted_at')) {
                // Permanently delete soft-deleted records first
                DB::table($table)->whereNotNull('deleted_at')->delete();

                // Drop the column
                Schema::table($table, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'users',
            'admins',
            'agents',
            'players',
            'employees',
            'financial_records',
            'deals',
            'contracts',
            'club_contracts',
            'training_programs',
            'player_physical_reports',
            'nutrition_programs',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }
};
