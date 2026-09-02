<?php
/*
 * Developed by Antigravity (Google DeepMind)
 */

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
        // Update players who have a club to have deal_status = 'SIGNED'
        DB::table('players')
            ->whereNotNull('club')
            ->where('club', '<>', '')
            ->update(['deal_status' => 'SIGNED']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No simple way to reverse this without knowing previous values
    }
};
