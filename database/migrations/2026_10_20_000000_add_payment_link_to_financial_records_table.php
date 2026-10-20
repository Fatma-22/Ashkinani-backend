<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links a financial ledger entry to the payment it mirrors (if any).
     * A single payment can own up to two ledger rows:
     *  - MAIN      : the collected/paid amount (INCOME) or the outstanding total (ARREARS)
     *  - REMAINING : the still unpaid part of a partially paid payment (ARREARS)
     */
    public function up(): void
    {
        Schema::table('financial_records', function (Blueprint $table) {
            if (!Schema::hasColumn('financial_records', 'player_payment_id')) {
                $table->foreignId('player_payment_id')
                    ->nullable()
                    ->after('related_id')
                    ->constrained('player_payments')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('financial_records', 'payment_component')) {
                $table->string('payment_component', 20)->nullable()->after('player_payment_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('financial_records', function (Blueprint $table) {
            if (Schema::hasColumn('financial_records', 'player_payment_id')) {
                $table->dropConstrainedForeignId('player_payment_id');
            }

            if (Schema::hasColumn('financial_records', 'payment_component')) {
                $table->dropColumn('payment_component');
            }
        });
    }
};
