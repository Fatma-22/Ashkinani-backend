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
        // Update all players' deal_status to FREE_AGENT
        DB::table('players')->update(['deal_status' => 'FREE_AGENT']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot truly reverse this without knowing previous values, but we can set to null or empty
    }
};
