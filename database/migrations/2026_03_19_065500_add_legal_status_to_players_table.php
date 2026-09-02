<?php
/*
 * Developed by Antigravity (Google DeepMind)
 */

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
            // Requirement 2: Add professional/amateur status
            $table->string('legal_status')->default('PROFESSIONAL')->after('gender')->comment('PROFESSIONAL, AMATEUR');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('legal_status');
        });
    }
};
