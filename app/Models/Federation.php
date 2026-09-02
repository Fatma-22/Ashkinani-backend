<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Federation extends Model
{
    use HasFactory;

    protected $fillable = [
        'sport_name',
        'logo_path'
    ];

    /**
     * Get the full URL for the logo.
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }

    protected $appends = ['logo_url'];
}
