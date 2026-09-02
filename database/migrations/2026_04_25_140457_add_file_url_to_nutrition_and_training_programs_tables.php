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
        Schema::table('nutrition_programs', function (Blueprint $table) {
            $table->string('file_url', 2048)->nullable()->after('is_active');
        });

        Schema::table('training_programs', function (Blueprint $table) {
            $table->string('file_url', 2048)->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nutrition_programs', function (Blueprint $table) {
            $table->dropColumn('file_url');
        });

        Schema::table('training_programs', function (Blueprint $table) {
            $table->dropColumn('file_url');
        });
    }
};
