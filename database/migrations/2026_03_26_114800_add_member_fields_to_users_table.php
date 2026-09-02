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
        Schema::table('users', function (Blueprint $blueprint) {
            $blueprint->string('phone')->nullable()->after('email');
            $blueprint->string('country')->nullable()->after('phone');
            $blueprint->string('member_type')->nullable()->after('role'); // PLAYER, COACH, SCOUT, CLUB, OTHER
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['phone', 'country', 'member_type']);
        });
    }
};
