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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('agent_id')->nullable()->constrained('agents')->onDelete('set null');
            $table->enum('profile_role', ['PLAYER', 'COACH'])->default('PLAYER');

            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('national_id')->nullable();
            $table->string('nationality')->index();
            $table->string('nationality_ar')->nullable();
            $table->date('date_of_birth');
            $table->enum('gender', ['MALE', 'FEMALE'])->default('MALE');

            $table->string('sport')->index();
            $table->string('position')->index();
            $table->string('club')->nullable();
            $table->string('club_ar')->nullable();
            $table->string('club_logo', 2048)->nullable();
            $table->integer('jersey_number')->nullable();
            $table->enum('preferred_foot', ['LEFT', 'RIGHT', 'BOTH'])->nullable();

            $table->enum('deal_status', ['SIGNED', 'FREE_AGENT', 'TRANSFER_LISTED', 'NEGOTIATION'])->default('FREE_AGENT')->index();
            $table->decimal('market_value', 15, 2)->default(0)->index();
            $table->index(['sport', 'position']);

            $table->integer('height')->nullable();
            $table->integer('weight')->nullable();

            $table->json('previous_clubs')->nullable();
            $table->json('achievements')->nullable();
            $table->json('achievements_ar')->nullable();
            $table->json('current_stats')->nullable();

            $table->text('bio')->nullable();
            $table->text('bio_ar')->nullable();
            $table->text('notes')->nullable();
            $table->text('notes_ar')->nullable();

            $table->json('visibility_settings');
            $table->boolean('is_visible')->default(false)->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
