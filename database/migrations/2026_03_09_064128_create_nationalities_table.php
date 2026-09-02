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
        Schema::create('nationalities', function (Blueprint $column) {
            $column->id();
            $column->string('name_en');
            $column->string('name_ar');
            $column->string('category')->default('FOREIGN'); // ARAB, FOREIGN
            $column->integer('sort_order')->default(999);
            $column->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nationalities');
    }
};
