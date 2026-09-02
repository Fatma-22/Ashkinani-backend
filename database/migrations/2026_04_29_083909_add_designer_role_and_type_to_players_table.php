<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('designer_type')->nullable()->after('profile_role');
        });
        
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE players MODIFY COLUMN `profile_role` ENUM('PLAYER', 'COACH', 'ADMINISTRATOR', 'REFEREE', 'PHOTOGRAPHER', 'DESIGNER') NULL DEFAULT 'PLAYER'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE players MODIFY COLUMN `profile_role` ENUM('PLAYER', 'COACH', 'ADMINISTRATOR', 'REFEREE', 'PHOTOGRAPHER') NULL DEFAULT 'PLAYER'");
        
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('designer_type');
        });
    }
};
