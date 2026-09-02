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
        // To avoid issues with doctrine/dbal and complex types, we drop and recreate
        // Note: This will result in data loss for this column. In a production environment with critical data,
        // we would normally migrate the data, but here we prioritize simplicity as per user request flow.
        Schema::table('players', function (Blueprint $table) {
            $table->integer('date_of_birth')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->change();
        });
    }
};
