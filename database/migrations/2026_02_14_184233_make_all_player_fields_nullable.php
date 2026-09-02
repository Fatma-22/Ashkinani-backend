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
            $table->string('name')->nullable()->change();
            $table->string('sport')->nullable()->change();
            $table->string('nationality')->nullable()->change();
            $table->date('date_of_birth')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('sport')->nullable(false)->change();
            $table->string('nationality')->nullable(false)->change();
            $table->date('date_of_birth')->nullable(false)->change();
        });
    }
};
