<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            // Volleyball-specific: match statistics PDF (optional)
            $table->string('volleyball_stats_pdf')->nullable()->after('drive_url');
            // Volleyball-specific: player ranking image (optional)
            $table->string('volleyball_ranking_image')->nullable()->after('volleyball_stats_pdf');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['volleyball_stats_pdf', 'volleyball_ranking_image']);
        });
    }
};
