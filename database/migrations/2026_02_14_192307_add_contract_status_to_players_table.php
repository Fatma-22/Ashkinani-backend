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
        Schema::table('players', function (Blueprint $table) {
            $table->enum('contract_status', ['ACTIVE', 'PENDING', 'EXPIRED', 'NEGOTIATION'])->default('ACTIVE')->after('contract_duration');
            $table->enum('gender', ['MALE', 'FEMALE'])->default('MALE')->change(); // Ensure gender exists or update it
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['contract_status']);
        });
    }
};
