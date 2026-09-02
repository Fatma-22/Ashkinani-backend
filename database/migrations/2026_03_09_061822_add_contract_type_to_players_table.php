<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add contract_type to players table
        Schema::table('players', function (Blueprint $table) {
            $table->enum('contract_type', ['PROFESSIONAL', 'YOUTH', 'LOAN', 'AMATEUR'])
                ->default('PROFESSIONAL')
                ->after('contract_status');
        });

        // 2. Update existing contracts table enum if necessary (if it exists)
        // Note: SQLite doesn't support modifying enums easily, but assuming MySQL/PostgreSQL
        if (Schema::hasTable('contracts')) {
            DB::statement("ALTER TABLE contracts MODIFY COLUMN type ENUM('PROFESSIONAL', 'YOUTH', 'LOAN', 'AMATEUR') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('contract_type');
        });

        if (Schema::hasTable('contracts')) {
            DB::statement("ALTER TABLE contracts MODIFY COLUMN type ENUM('PROFESSIONAL', 'YOUTH', 'LOAN') NOT NULL");
        }
    }
};
