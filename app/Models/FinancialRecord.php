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
        'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function related()
    {
        return $this->morphTo();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
