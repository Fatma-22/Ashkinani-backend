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
        Schema::table('players', function (Blueprint $table) {
            $table->decimal('contract_fees', 15, 2)->nullable()->after('contract_duration');
            $table->enum('contract_fees_type', ['FIXED', 'PERCENTAGE'])->default('FIXED')->after('contract_fees');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->decimal('fees_amount', 15, 2)->nullable()->after('currency');
            $table->enum('fees_type', ['FIXED', 'PERCENTAGE'])->default('FIXED')->after('fees_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['contract_fees', 'contract_fees_type']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['fees_amount', 'fees_type']);
        });
    }
};
