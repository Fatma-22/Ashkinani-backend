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
            $table->string('name')->nullable()->change();
            $table->string('position')->nullable()->change();
            $table->string('department')->nullable()->change();
            $table->decimal('salary', 15, 2)->nullable()->change();
            $table->date('hire_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('position')->nullable(false)->change();
            $table->string('department')->nullable(false)->change();
            $table->decimal('salary', 15, 2)->nullable(false)->change();
            $table->date('hire_date')->nullable(false)->change();
        });
    }
};
