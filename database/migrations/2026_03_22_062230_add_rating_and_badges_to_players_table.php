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
            $table->unsignedTinyInteger('rating')->default(0)->after('is_visible');
            $table->unsignedTinyInteger('fitness_rating')->default(0)->after('rating');
            $table->unsignedTinyInteger('speed_rating')->default(0)->after('fitness_rating');
            $table->unsignedTinyInteger('technique_rating')->default(0)->after('speed_rating');
            $table->boolean('is_verified')->default(false)->after('technique_rating');
            $table->boolean('is_rising_talent')->default(false)->after('is_verified');
            $table->boolean('top_agent_pick')->default(false)->after('is_rising_talent');
            $table->text('technical_report')->nullable()->after('top_agent_pick');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn([
                'rating', 
                'fitness_rating', 
                'speed_rating', 
                'technique_rating', 
                'is_verified', 
                'is_rising_talent', 
                'top_agent_pick', 
                'technical_report'
            ]);
        });
    }
};
