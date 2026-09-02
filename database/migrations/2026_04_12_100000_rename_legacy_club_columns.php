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
            $table->renameColumn('club', 'club_name_legacy');
            $table->renameColumn('club_ar', 'club_name_ar_legacy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->renameColumn('club_name_legacy', 'club');
            $table->renameColumn('club_name_ar_legacy', 'club_ar');
        });
    }
};
