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
        Schema::table('employees', function (Blueprint $table) {
            $table->integer('year_of_birth')->nullable()->after('name_ar');
            $table->string('nationality')->nullable()->after('year_of_birth');
            $table->string('nationality_ar')->nullable()->after('nationality');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['year_of_birth', 'nationality', 'nationality_ar']);
        });
    }
};
