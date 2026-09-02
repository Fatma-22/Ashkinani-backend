<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsor_images', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('sponsor_id')->constrained()->cascadeOnDelete();
            $blueprint->string('image_path');
            $blueprint->integer('sort_order')->default(0);
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsor_images');
    }
};
