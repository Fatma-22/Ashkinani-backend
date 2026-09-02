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
        // Update the enum list for profile_role column
        DB::statement("ALTER TABLE players MODIFY COLUMN `profile_role` ENUM('PLAYER', 'COACH', 'ADMINISTRATOR', 'REFEREE', 'PHOTOGRAPHER') NULL DEFAULT 'PLAYER'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum list (be careful if data exists for new roles)
        DB::statement("ALTER TABLE players MODIFY COLUMN `profile_role` ENUM('PLAYER', 'COACH') NULL DEFAULT 'PLAYER'");
    }
};
