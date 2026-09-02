<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('title_en')->nullable()->change();
            $table->string('title_ar')->nullable()->change();
            $table->longText('content_en')->nullable()->change();
            $table->longText('content_ar')->nullable()->change();
            $table->string('main_image_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('title_en')->nullable(false)->change();
            $table->string('title_ar')->nullable(false)->change();
            $table->longText('content_en')->nullable(false)->change();
            $table->longText('content_ar')->nullable(false)->change();
            $table->string('main_image_path')->nullable(false)->change();
        });
    }
};
