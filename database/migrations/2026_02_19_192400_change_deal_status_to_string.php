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
            $table->string('deal_status')->default('FREE_AGENT')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->enum('deal_status', ['SIGNED', 'FREE_AGENT', 'TRANSFER_LISTED', 'NEGOTIATION'])->default('FREE_AGENT')->change();
        });
    }
};
