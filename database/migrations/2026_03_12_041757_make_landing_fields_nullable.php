<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->string('name_en')->nullable()->change();
            $table->string('name_ar')->nullable()->change();
            $table->string('logo_path')->nullable()->change();
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->string('title_en')->nullable()->change();
            $table->string('title_ar')->nullable()->change();
        });

        Schema::table('ads', function (Blueprint $table) {
            $table->string('image_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->string('name_en')->nullable(false)->change();
            $table->string('name_ar')->nullable(false)->change();
            $table->string('logo_path')->nullable(false)->change();
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->string('title_en')->nullable(false)->change();
            $table->string('title_ar')->nullable(false)->change();
        });

        Schema::table('ads', function (Blueprint $table) {
            $table->string('image_path')->nullable(false)->change();
        });
    }
};
