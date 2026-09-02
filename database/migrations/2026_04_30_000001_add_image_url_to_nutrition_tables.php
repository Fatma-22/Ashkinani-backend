<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_physical_reports', function (Blueprint $table) {
            $table->string('image_url', 2048)->nullable()->after('file_url');
        });

        Schema::table('nutrition_programs', function (Blueprint $table) {
            $table->string('image_url', 2048)->nullable()->after('file_url');
        });

        Schema::table('training_programs', function (Blueprint $table) {
            $table->string('image_url', 2048)->nullable()->after('file_url');
        });
    }

    public function down(): void
    {
        Schema::table('player_physical_reports', function (Blueprint $table) {
            $table->dropColumn('image_url');
        });
        Schema::table('nutrition_programs', function (Blueprint $table) {
            $table->dropColumn('image_url');
        });
        Schema::table('training_programs', function (Blueprint $table) {
            $table->dropColumn('image_url');
        });
    }
};
