<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all players who are Kuwaiti to be marked as born in Kuwait
        DB::table('players')
            ->where(function ($query) {
                $query->where('nationality', 'Kuwaiti')
                      ->orWhere('nationality', 'Kuwait')
                      ->orWhere('nationality_ar', 'كويتي');
            })
            ->update(['born_in_kuwait' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No easy way to reverse this without affecting manually set data, 
        // but we could set all to false if needed.
    }
};
