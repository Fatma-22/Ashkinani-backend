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
        Schema::table('coach_certificates', function (Blueprint $table) {
            // Change enum columns to string to allow more flexible values from the UI
            $table->string('certificate_type')->change();
            $table->string('level')->change();
            $table->string('source_type')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coach_certificates', function (Blueprint $table) {
            // Reverting back to enums if needed (though not recommended given the UI choices)
            $table->enum('certificate_type', [
                'Coaching License',
                'Fitness / Conditioning',
                'First Aid / CPR',
                'Sports Nutrition',
                'Sports Psychology',
                'Other'
            ])->change();
            
            $table->enum('level', [
                'Beginner',
                'Intermediate',
                'Advanced',
                'Professional'
            ])->change();

            $table->enum('source_type', [
                'Sports Federation',
                'Academy',
                'University',
                'Online Course',
                'Club Training',
                'Other'
            ])->change();
        });
    }
};
