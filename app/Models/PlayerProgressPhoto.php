<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlayerProgressPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'photo_url',
        'view_type',
        'stage',
        'captured_at',
        'notes'
    ];

    protected $casts = [
        'captured_at' => 'date',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
