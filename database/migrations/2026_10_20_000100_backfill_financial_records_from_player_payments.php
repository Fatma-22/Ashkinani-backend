<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backfill ledger rows for payments that existed before the sync feature.
     * New payments are mirrored automatically by FinancialSyncService.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('financial_records', 'player_payment_id')) {
            return;
        }

        // Normalize legacy morph aliases (if any) to fully qualified model names.
        $legacyTypes = [
            'player' => 'App\\Models\\Player',
            'employee' => 'App\\Models\\Employee',
            'member' => 'App\\Models\\User',
            'user' => 'App\\Models\\User',
            'agent' => 'App\\Models\\Agent',
            'admin' => 'App\\Models\\Admin',
        ];

        foreach ($legacyTypes as $alias => $class) {
            DB::table('financial_records')
                ->where('related_type', $alias)
                ->update(['related_type' => $class]);
        }

        DB::table('player_payments')->orderBy('id')->chunkById(200, function ($payments) {
            foreach ($payments as $payment) {
                $alreadyLinked = DB::table('financial_records')
                    ->where('player_payment_id', $payment->id)
                    ->exists();

                if ($alreadyLinked) {
                    continue;
                }

                $playerName = DB::table('players')->where('id', $payment->player_id)->value('name');

                $total = max(0, (float) $payment->total_amount);
                $paid = max(0, (float) $payment->paid_amount);

                if (strtoupper((string) $payment->status) === 'PAID') {
                    $paid = $total;
                }

                $paid = min($paid, $total);
                $remaining = max(0, $total - $paid);

                $createdDate = $payment->created_at
                    ? substr((string) $payment->created_at, 0, 10)
                    : now()->toDateString();

                $transactionDate = $payment->payment_date
                    ?: ($payment->due_date ?: $createdDate);

                $base = [
                    'category' => 'PlayerPayment',
                    'category_ar' => 'دفعة لاعب',
                    'currency' => $payment->currency ?: 'USD',
                    'description' => $payment->title ?: 'Player payment',
                    'description_ar' => $payment->title,
                    'related_type' => 'App\\Models\\Player',
                    'related_id' => $payment->player_id,
                    'related_to' => $playerName,
                    'invoice_path' => $payment->receipt_path,
                    'player_payment_id' => $payment->id,
                    'created_by' => $payment->created_by,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                DB::table('financial_records')->insert(array_merge($base, [
                    'type' => $paid > 0 ? 'INCOME' : 'ARREARS',
                    'amount' => $paid > 0 ? $paid : $total,
                    'transaction_date' => $transactionDate,
                    'payment_component' => 'MAIN',
                ]));

                if ($paid > 0 && $remaining > 0) {
                    DB::table('financial_records')->insert(array_merge($base, [
                        'type' => 'ARREARS',
                        'amount' => $remaining,
                        'transaction_date' => $payment->due_date ?: $createdDate,
                        'payment_component' => 'REMAINING',
                    ]));
                }
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('financial_records', 'player_payment_id')) {
            DB::table('financial_records')->whereNotNull('player_payment_id')->delete();
        }
    }
};
