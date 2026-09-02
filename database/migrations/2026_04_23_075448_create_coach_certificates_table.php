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
        Schema::create('coach_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('players')->onDelete('cascade');
            $table->string('certificate_name');
            $table->enum('certificate_type', [
                'Coaching License',
                'Fitness / Conditioning',
                'First Aid / CPR',
                'Sports Nutrition',
                'Sports Psychology',
                'Other'
            ]);
            $table->string('issuing_body');
            $table->integer('year_obtained')->nullable();
            $table->enum('level', [
                'Beginner',
                'Intermediate',
                'Advanced',
                'Professional'
            ]);
            $table->string('certificate_number')->nullable();
            $table->enum('source_type', [
                'Sports Federation',
                'Academy',
                'University',
                'Online Course',
                'Club Training',
                'Other'
            ]);
            $table->string('certificate_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coach_certificates');
    }
};
