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
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->string('from_club')->nullable();
            $table->string('to_club')->nullable();
            $table->date('deal_date')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('currency')->nullable()->default('USD');
            $table->string('type')->nullable(); // e.g., Permanent, Loan
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
