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
        Schema::table('deals', function (Blueprint $table) {
            $table->foreignId('player_id')->nullable()->change();
            $table->string('manual_player_name')->nullable()->after('player_id');
            $table->string('manual_player_name_ar')->nullable()->after('manual_player_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->foreignId('player_id')->nullable(false)->change();
            $table->dropColumn(['manual_player_name', 'manual_player_name_ar']);
        });
    }
};
