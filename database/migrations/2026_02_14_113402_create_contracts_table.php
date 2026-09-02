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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('agents')->onDelete('set null');

            $table->enum('type', ['PROFESSIONAL', 'YOUTH', 'LOAN'])->index();
            $table->enum('status', ['ACTIVE', 'PENDING', 'EXPIRED', 'NEGOTIATION', 'TERMINATED'])->default('PENDING')->index();

            $table->date('start_date');
            $table->date('end_date');

            $table->decimal('annual_salary', 15, 2)->default(0);
            $table->decimal('signing_bonus', 15, 2)->nullable()->default(0);
            $table->string('currency', 10)->default('USD');

            $table->string('file_url', 2048)->nullable();
            $table->text('notes')->nullable();
            $table->text('notes_ar')->nullable();

            $table->boolean('is_visible')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
