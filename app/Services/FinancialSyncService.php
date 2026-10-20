<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Agent;
use App\Models\Employee;
use App\Models\FinancialRecord;
use App\Models\Player;
use App\Models\PlayerPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the financial ledger (financial_records) and the person payments
 * (player_payments) in sync in both directions.
 *
 * A payment owns one or two ledger rows:
 *  - MAIN      : the collected amount (INCOME) or the outstanding total (ARREARS)
 *  - REMAINING : the unpaid part of a partially paid payment (ARREARS)
 */
class FinancialSyncService
{
    public const COMPONENT_MAIN = 'MAIN';
    public const COMPONENT_REMAINING = 'REMAINING';

    /** Guard against recursive sync loops. */
    protected bool $syncing = false;

    /**
     * Map of the public/short person types to their Eloquent models.
     *
     * @return array<string, class-string<Model>>
     */
    public function personTypes(): array
    {
        return [
            'player' => Player::class,
            'employee' => Employee::class,
            'member' => User::class,
            'agent' => Agent::class,
            'admin' => Admin::class,
        ];
    }

    public function shortType(?string $type): ?string
    {
        if (!$type) {
            return null;
        }

        foreach ($this->personTypes() as $short => $class) {
            if ($type === $class || is_a($type, $class, true)) {
                return $short;
            }
        }

        return strtolower(class_basename($type));
    }

    public function modelForType(?string $type): ?string
    {
        if (!$type) {
            return null;
        }

        return $this->personTypes()[strtolower($type)] ?? null;
    }

    public function resolvePerson(string $type, mixed $id): ?Model
    {
        $class = $this->modelForType($type);

        if (!$class || !$id) {
            return null;
        }

        return $class::query()->find($id);
    }

    public function personName(?Model $person): ?string
    {
        if (!$person) {
            return null;
        }

        return $person->name ?: ($person->name_ar ?? null);
    }

    /**
     * Create/update the payment mirroring a financial ledger row.
     * Only player-linked rows are mirrored because payments belong to players.
     */
    public function syncRecordToPayment(FinancialRecord $record): void
    {
        if ($this->syncing || $record->payment_component === self::COMPONENT_REMAINING) {
            return;
        }

        $player = $this->resolveRecordPlayer($record);

        if (!$player) {
            return;
        }

        $this->syncing = true;

        try {
            DB::transaction(function () use ($record, $player) {
                $payment = $record->player_payment_id
                    ? PlayerPayment::find($record->player_payment_id)
                    : null;

                if (!$payment) {
                    $payment = new PlayerPayment();
                    $payment->player_id = $player->id;
                    $payment->created_by = $record->created_by ?: auth()->id();
                }

                $this->applyRecordToPayment($record, $payment);
                $payment->save();

                $record->forceFill([
                    'player_payment_id' => $payment->id,
                    'payment_component' => self::COMPONENT_MAIN,
                ])->save();

                $this->syncPaymentDerivedRecords($payment, $record);
            });
        } finally {
            $this->syncing = false;
        }
    }

    /**
     * Create/update the ledger rows mirroring a player payment.
     */
    public function syncPaymentToRecords(PlayerPayment $payment): void
    {
        if ($this->syncing) {
            return;
        }

        $this->syncing = true;

        try {
            DB::transaction(fn () => $this->syncPaymentDerivedRecords($payment));
        } finally {
            $this->syncing = false;
        }
    }

    /**
     * When the outstanding (REMAINING) ledger row is edited from the
     * financials page, adjust the payment total and re-sync both rows.
     */
    public function applyRemainingRecordToPayment(FinancialRecord $record): void
    {
        if ($record->payment_component !== self::COMPONENT_REMAINING || !$record->player_payment_id) {
            return;
        }

        $payment = PlayerPayment::find($record->player_payment_id);

        if (!$payment) {
            return;
        }

        $main = FinancialRecord::query()
            ->where('player_payment_id', $payment->id)
            ->where('payment_component', self::COMPONENT_MAIN)
            ->first();

        $paid = $main ? (float) $main->amount : (float) $payment->paid_amount;
        $remaining = max(0, (float) $record->amount);

        $payment->total_amount = $paid + $remaining;
        $payment->paid_amount = $paid;

        if ($remaining <= 0) {
            $payment->status = 'PAID';
        } elseif ($paid > 0) {
            $payment->status = 'PARTIAL';
        } elseif (!$payment->status) {
            $payment->status = 'PENDING';
        }

        $payment->save();

        $this->syncPaymentToRecords($payment);
    }

    /**
     * Deleting the outstanding row means the remaining amount was waived:
     * the payment becomes fully paid and the row disappears.
     */
    public function removeRemainingRecord(FinancialRecord $record): void
    {
        if ($record->payment_component !== self::COMPONENT_REMAINING || !$record->player_payment_id) {
            $record->delete();

            return;
        }

        $payment = PlayerPayment::find($record->player_payment_id);

        if (!$payment) {
            $record->delete();

            return;
        }

        $main = FinancialRecord::query()
            ->where('player_payment_id', $payment->id)
            ->where('payment_component', self::COMPONENT_MAIN)
            ->first();

        $paid = $main ? (float) $main->amount : (float) $payment->paid_amount;

        $payment->total_amount = $paid;
        $payment->paid_amount = $paid;
        $payment->status = 'PAID';
        $payment->save();

        $this->syncPaymentToRecords($payment);
    }

    /**
     * Remove every ledger row generated by a payment.
     */
    public function deletePaymentMirrors(PlayerPayment $payment): void
    {
        FinancialRecord::query()
            ->where('player_payment_id', $payment->id)
            ->delete();
    }

    /**
     * Delete a ledger row coming from the financials page.
     * Returns true when the linked payment (and its rows) were deleted as well.
     */
    public function deleteForRecord(FinancialRecord $record): bool
    {
        if (!$record->player_payment_id || $record->payment_component === self::COMPONENT_REMAINING) {
            return false;
        }

        $payment = PlayerPayment::find($record->player_payment_id);

        if (!$payment) {
            return false;
        }

        DB::transaction(function () use ($payment) {
            FinancialRecord::query()
                ->where('player_payment_id', $payment->id)
                ->delete();

            $payment->delete();
        });

        return true;
    }

    /**
     * Break the link between a ledger row and its payment without deleting the row.
     * Used when the row is re-assigned to another person or unlinked.
     */
    public function detachRecordFromPayment(FinancialRecord $record): void
    {
        $paymentId = $record->player_payment_id;

        if (!$paymentId) {
            return;
        }

        DB::transaction(function () use ($record, $paymentId) {
            FinancialRecord::query()
                ->where('player_payment_id', $paymentId)
                ->where('payment_component', self::COMPONENT_REMAINING)
                ->delete();

            PlayerPayment::query()->whereKey($paymentId)->delete();

            FinancialRecord::query()->whereKey($record->id)->update([
                'player_payment_id' => null,
                'payment_component' => null,
            ]);

            $record->player_payment_id = null;
            $record->payment_component = null;
        });
    }

    protected function resolveRecordPlayer(FinancialRecord $record): ?Player
    {
        if (!$record->related_id) {
            return null;
        }

        if ($this->shortType($record->related_type) !== 'player') {
            return null;
        }

        return Player::query()->find($record->related_id);
    }

    protected function applyRecordToPayment(FinancialRecord $record, PlayerPayment $payment): void
    {
        $amount = max(0, (float) $record->amount);
        $type = strtoupper((string) $record->type);
        $date = $this->recordDate($record);

        $payment->player_id = $record->related_id ?: $payment->player_id;

        $title = $record->description_ar ?: $record->description ?: $record->category;
        if ($title) {
            $payment->title = mb_substr((string) $title, 0, 255);
        }

        if ($record->currency) {
            $payment->currency = $record->currency;
        }

        if ($record->description) {
            $payment->notes = $record->description;
        }

        $payment->receipt_path = $record->invoice_path;

        if ($type === 'ARREARS') {
            $payment->total_amount = $amount;
            $payment->paid_amount = 0;
            $payment->status = $date->isPast() ? 'OVERDUE' : 'PENDING';
            $payment->due_date = $date->toDateString();
            $payment->payment_date = null;

            return;
        }

        // INCOME and EXPENSE rows represent completed movements.
        // When editing the collected part of a partially paid payment,
        // keep the outstanding part untouched.
        $outstanding = 0;

        if ($type === 'INCOME' && $payment->exists && $payment->id) {
            $outstanding = (float) FinancialRecord::query()
                ->where('player_payment_id', $payment->id)
                ->where('payment_component', self::COMPONENT_REMAINING)
                ->sum('amount');
        }

        $payment->total_amount = $amount + $outstanding;
        $payment->paid_amount = $amount;
        $payment->status = $outstanding > 0 ? 'PARTIAL' : 'PAID';
        $payment->payment_date = $date->toDateString();
        $payment->due_date = $outstanding > 0 ? $payment->due_date : null;
    }

    protected function recordDate(FinancialRecord $record): Carbon
    {
        return $record->transaction_date
            ? Carbon::parse($record->transaction_date)
            : now();
    }

    protected function syncPaymentDerivedRecords(PlayerPayment $payment, ?FinancialRecord $main = null): void
    {
        $player = $payment->player ?: Player::query()->find($payment->player_id);

        if (!$player) {
            return;
        }

        $total = max(0, (float) $payment->total_amount);
        $paid = max(0, (float) $payment->paid_amount);

        if (strtoupper((string) $payment->status) === 'PAID') {
            $paid = $total;
        }

        $paid = min($paid, $total);
        $remaining = $paid > 0 ? max(0, $total - $paid) : 0;

        if (!$main) {
            $main = FinancialRecord::query()
                ->where('player_payment_id', $payment->id)
                ->where('payment_component', self::COMPONENT_MAIN)
                ->first();
        }

        if (!$main) {
            $main = new FinancialRecord();
            $main->player_payment_id = $payment->id;
            $main->payment_component = self::COMPONENT_MAIN;
        }

        $isExpense = strtoupper((string) $main->type) === 'EXPENSE';

        if ($isExpense) {
            // Expenses keep their nature; the unpaid part is not tracked as arrears.
            $remaining = 0;
            $main->type = 'EXPENSE';
            $main->amount = $total;
        } else {
            $main->type = $paid > 0 ? 'INCOME' : 'ARREARS';
            $main->amount = $paid > 0 ? $paid : $total;
        }

        $main->currency = $payment->currency ?: ($main->currency ?: 'USD');
        $main->related_type = Player::class;
        $main->related_id = $payment->player_id;
        $main->related_to = $this->personName($player) ?: $main->related_to;
        $main->category = $main->category ?: 'PlayerPayment';
        $main->category_ar = $main->category_ar ?: 'دفعة لاعب';

        if ($payment->title) {
            $main->description = $payment->title;
            $main->description_ar = $main->description_ar ?: $payment->title;
        }

        $main->description = $main->description ?: ($payment->title ?: 'Player payment');
        $main->description_ar = $main->description_ar ?: ($payment->title ?: 'دفعة لاعب');
        $main->invoice_path = $payment->receipt_path;
        $main->created_by = $main->created_by ?: ($payment->created_by ?: auth()->id());
        $main->transaction_date = $this->paymentDate($payment, $paid > 0, $main);
        $main->save();

        $this->syncRemainingRecord($payment, $player, $main, $remaining);
    }

    protected function syncRemainingRecord(
        PlayerPayment $payment,
        Player $player,
        FinancialRecord $main,
        float $remaining
    ): void {
        $remainingRecord = FinancialRecord::query()
            ->where('player_payment_id', $payment->id)
            ->where('payment_component', self::COMPONENT_REMAINING)
            ->first();

        if ($remaining <= 0) {
            if ($remainingRecord) {
                $remainingRecord->delete();
            }

            return;
        }

        if (!$remainingRecord) {
            $remainingRecord = new FinancialRecord();
            $remainingRecord->player_payment_id = $payment->id;
            $remainingRecord->payment_component = self::COMPONENT_REMAINING;
            $remainingRecord->category = $main->category;
            $remainingRecord->category_ar = $main->category_ar;
            $remainingRecord->created_by = $payment->created_by ?: auth()->id();
        }

        $remainingRecord->type = 'ARREARS';
        $remainingRecord->amount = $remaining;
        $remainingRecord->currency = $main->currency;
        $remainingRecord->description = $main->description;
        $remainingRecord->description_ar = $main->description_ar;
        $remainingRecord->related_type = Player::class;
        $remainingRecord->related_id = $payment->player_id;
        $remainingRecord->related_to = $this->personName($player) ?: $main->related_to;
        $remainingRecord->invoice_path = $payment->receipt_path;
        $remainingRecord->transaction_date = $payment->due_date
            ? Carbon::parse($payment->due_date)->toDateString()
            : now()->toDateString();
        $remainingRecord->save();
    }

    protected function paymentDate(PlayerPayment $payment, bool $paid, FinancialRecord $existing): string
    {
        $date = $paid
            ? ($payment->payment_date ?: $payment->due_date)
            : ($payment->due_date ?: $payment->payment_date);

        if (!$date && $existing->transaction_date) {
            $date = $existing->transaction_date;
        }

        return Carbon::parse($date ?: now())->toDateString();
    }
}
