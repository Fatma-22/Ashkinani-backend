<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            // Volleyball-specific: spike reach height in cm
            $table->unsignedSmallInteger('volleyball_spike_reach')->nullable()->after('volleyball_ranking_image')
                  ->comment('Spike (attack) reach height in cm');
            // Volleyball-specific: block reach height in cm
            $table->unsignedSmallInteger('volleyball_block_reach')->nullable()->after('volleyball_spike_reach')
                  ->comment('Block reach height in cm');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['volleyball_spike_reach', 'volleyball_block_reach']);
        });
    }
};
