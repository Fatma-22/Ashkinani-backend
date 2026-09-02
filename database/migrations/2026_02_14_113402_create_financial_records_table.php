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
        Schema::create('financial_records', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['INCOME', 'EXPENSE'])->index();
            $table->string('category');
            $table->string('category_ar')->nullable();

            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('USD');

            $table->text('description');
            $table->text('description_ar')->nullable();
            $table->date('transaction_date')->index();

            // Polymorphic Relation
            $table->nullableMorphs('related');

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_records');
    }
};
