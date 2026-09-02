<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'agent_id',
        'type',
        'status',
        'start_date',
        'end_date',
        'annual_salary',
        'signing_bonus',
        'currency',
        'file_url',
        'notes',
        'notes_ar',
        'is_visible',
        'fees_amount',
        'fees_type'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'annual_salary' => 'decimal:2',
        'signing_bonus' => 'decimal:2',
        'is_visible' => 'boolean',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}
