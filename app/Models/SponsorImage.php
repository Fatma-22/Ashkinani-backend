<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SponsorImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'sponsor_id',
        'image_path',
        'sort_order'
    ];

    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class);
    }
}
