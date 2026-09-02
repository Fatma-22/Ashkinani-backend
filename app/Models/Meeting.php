<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $fillable = [
        'title',
        'meeting_type',
        'duration',
        'fees',
        'description',
        'meeting_date',
        'meeting_time',
        'location',
        'related_person_type',
        'related_person_name',
        'player_id',
        'status',
        'created_by'
    ];

    protected $casts = [
        'meeting_date' => 'date',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
