<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admin extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'avatar',
        'permissions',
        'is_active',
        'is_scout',
        'created_by'
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'is_scout' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Players scouted by this admin/scout.
     */
    public function scoutedPlayers()
    {
        return $this->hasMany(Player::class, 'scout_id');
    }
}
