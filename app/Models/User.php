<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'is_active',
        'phone',
        'country',
        'member_type',
        'national_id',
        'organization',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted()
    {
        static::deleting(function ($user) {
            // Cascade delete associated profiles to prevent orphans and phone conflicts
            if ($user->player) {
                $user->player->delete();
            }
            if ($user->agent) {
                // Dissociate players first
                $user->agent->players()->update(['agent_id' => null]);
                $user->agent->contracts()->update(['agent_id' => null]);
                $user->agent->delete();
            }
            if ($user->adminProfile) {
                $user->adminProfile->delete();
            }
        });
    }

    /**
     * Relationships
     */
    public function player()
    {
        return $this->hasOne(Player::class);
    }

    public function agent()
    {
        return $this->hasOne(Agent::class);
    }

    public function adminProfile()
    {
        return $this->hasOne(Admin::class);
    }
}
