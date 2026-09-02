<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'created_by',
        'title',
        'workout_plan',
        'recovery_plan',
        'start_date',
        'end_date',
        'is_active',
        'file_url',
        'image_url',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
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
