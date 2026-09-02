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
        Schema::table('financial_records', function (Blueprint $table) {
            $table->string('category')->nullable()->change();
            $table->decimal('amount', 15, 2)->nullable()->change();
            $table->text('description')->nullable()->change();
            $table->date('transaction_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_records', function (Blueprint $table) {
            $table->string('category')->nullable(false)->change();
            $table->decimal('amount', 15, 2)->nullable(false)->change();
            $table->text('description')->nullable(false)->change();
            $table->date('transaction_date')->nullable(false)->change();
        });
    }
};
