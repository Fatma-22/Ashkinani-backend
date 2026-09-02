<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'title_en',
        'title_ar',
        'description_en',
        'description_ar',
        'code',
        'image_path',
        'expiry_date',
        'is_active',
        'sponsor_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expiry_date' => 'date'
    ];

    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class);
    }
}
