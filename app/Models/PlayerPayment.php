<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlayerPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'title',
        'total_amount',
        'paid_amount',
        'currency',
        'due_date',
        'payment_date',
        'status',
        'receipt_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'payment_date' => 'date',
    ];

    protected $appends = ['remaining_amount', 'receipt_url'];

    public function getRemainingAmountAttribute(): string
    {
        return number_format(max(0, (float) $this->total_amount - (float) $this->paid_amount), 2, '.', '');
    }

    public function getReceiptUrlAttribute(): ?string
    {
        if (!$this->receipt_path) {
            return null;
        }

        if (filter_var($this->receipt_path, FILTER_VALIDATE_URL)) {
            return $this->receipt_path;
        }

        return asset('storage/' . $this->receipt_path);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
