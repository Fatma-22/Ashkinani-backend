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
        Schema::create('club_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');

            $table->string('club_name')->nullable();
            $table->string('club_name_ar')->nullable();
            $table->string('club_country')->nullable();
            $table->string('club_country_ar')->nullable();
            
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->string('file_url', 2048)->nullable();
            $table->text('notes')->nullable();
            $table->text('notes_ar')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_contracts');
    }
};
