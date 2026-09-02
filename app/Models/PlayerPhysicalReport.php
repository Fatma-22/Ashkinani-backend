<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlayerPhysicalReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'created_by',
        'weight',
        'height',
        'fat_percentage',
        'muscle_mass',
        'body_mass_index',
        'physical_assessment',
        'report_date',
        'additional_metrics',
        'file_url',
        'image_url'
    ];

    protected $casts = [
        'additional_metrics' => 'array',
        'report_date' => 'date',
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
