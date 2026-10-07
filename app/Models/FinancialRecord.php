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
        'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    protected $appends = ['invoice_url'];

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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
