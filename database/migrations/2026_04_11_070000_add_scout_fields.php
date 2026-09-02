<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add is_scout flag to admins table
        Schema::table('admins', function (Blueprint $table) {
            $table->boolean('is_scout')->default(false)->after('is_active');
        });

        // Add scout_id to players table (which admin/scout entered this player)
        Schema::table('players', function (Blueprint $table) {
            $table->foreignId('scout_id')->nullable()->after('agent_id')
                  ->constrained('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropForeign(['scout_id']);
            $table->dropColumn('scout_id');
        });

        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('is_scout');
        });
    }
};
