<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlayerDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'name',
        'url',
        'type',
        'start_date',
        'end_date',
        'uploaded_at'
    ];

    protected $casts = [
        'uploaded_at' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
