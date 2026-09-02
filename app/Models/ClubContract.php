<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClubContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'deal_id',
        'club_name',
        'club_name_ar',
        'club_country',
        'club_country_ar',
        'start_date',
        'end_date',
        'file_url',
        'notes',
        'notes_ar'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
