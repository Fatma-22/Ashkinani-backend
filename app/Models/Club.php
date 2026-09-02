<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Club extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_ar',
        'country',
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

    public function players()
    {
        return $this->hasMany(Player::class);
    }
}
