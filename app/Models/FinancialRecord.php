<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinancialRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'category',
        'category_ar',
        'amount',
        'currency',
        'description',
        'description_ar',
        'transaction_date',
        'related_type',
        'related_id',
        'related_to',
        'invoice_path',
        'player_payment_id',
        'payment_component',
        'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    protected $appends = ['invoice_url', 'related_person', 'is_synced'];


    public function getInvoiceUrlAttribute(): ?string
    {
        if (!$this->invoice_path) {
            return null;
        }

        if (filter_var($this->invoice_path, FILTER_VALIDATE_URL)) {
            return $this->invoice_path;
        }

        return asset('storage/' . $this->invoice_path);
    }

    public function related()
    {
        return $this->morphTo();
    }

    public function playerPayment()
    {
        return $this->belongsTo(PlayerPayment::class, 'player_payment_id');
    }

    public function getRelatedPersonAttribute(): ?array
    {
        $short = app(\App\Services\FinancialSyncService::class)->shortType($this->related_type);

        if (!$short || !$this->related_id) {
            return null;
        }

        try {
            $person = $this->related;
        } catch (\Throwable $e) {
            return null;
        }

        if (!$person) {
            return null;
        }

        $subtitle = null;

        if ($short === 'player') {
            $subtitle = $person->sport ?: null;
        } elseif ($short === 'employee') {
            $subtitle = $person->position ?: ($person->department ?? null);
        } elseif ($short === 'agent') {
            $subtitle = $person->company ?? null;
        } else {
            $subtitle = $person->email ?? null;
        }

        return [
            'type' => $short,
            'id' => $person->id,
            'name' => $person->name ?: ($person->name_ar ?? null),
            'name_ar' => $person->name_ar ?? null,
            'role' => $person->profile_role ?? $short,
            'subtitle' => $subtitle,
            'url' => $short === 'player' ? '/owner/players/' . $person->id : null,
        ];
    }

    public function getIsSyncedAttribute(): bool
    {
        return (bool) $this->player_payment_id;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
