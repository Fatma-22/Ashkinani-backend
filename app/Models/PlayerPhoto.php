<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlayerPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'url',
        'caption',
        'is_main'
    ];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
