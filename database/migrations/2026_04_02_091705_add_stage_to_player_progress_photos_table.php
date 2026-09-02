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
        Schema::table('player_progress_photos', function (Blueprint $table) {
            $table->enum('stage', ['BEFORE', 'DURING', 'AFTER'])->nullable()->after('view_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_progress_photos', function (Blueprint $table) {
            $table->dropColumn('stage');
        });
    }
};
