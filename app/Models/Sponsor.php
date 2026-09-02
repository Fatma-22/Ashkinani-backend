<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sponsor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ar',
        'type',
        'start_date',
        'end_date',
        'agreement_text',
        'agreement_text_ar',
        'services_en',
        'services_ar',
        'logo_path',
        'contract_path',
        'website_url',
        'sort_order',
        'is_active',
        'tier'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function players()
    {
        return $this->belongsToMany(Player::class, 'player_sponsor');
    }

    public function images()
    {
        return $this->hasMany(SponsorImage::class)->orderBy('sort_order');
    }

    public function discounts()
    {
        return $this->hasMany(Discount::class);
    }
}
