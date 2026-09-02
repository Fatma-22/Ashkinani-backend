<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'manual_player_name',
        'manual_player_name_ar',
        'manual_player_role',
        'manual_player_sport',
        'from_club',
        'from_club_ar',
        'to_club',
        'to_club_ar',
        'deal_date',
        'contract_start_date',
        'contract_end_date',
        'contract_url',
        'amount',
        'currency',
        'type',
        'notes',
    ];

    protected $casts = [
        'deal_date' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
