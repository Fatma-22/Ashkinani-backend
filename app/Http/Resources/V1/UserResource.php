<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'avatar' => $this->avatar ? (filter_var($this->avatar, FILTER_VALIDATE_URL) ? $this->avatar : asset('storage/' . $this->avatar)) : null,
            'isActive' => $this->is_active,
            'phone' => $this->phone,
            'country' => $this->country,
            'memberType' => $this->member_type,
            'nationalId' => $this->national_id,
            'organization' => $this->organization,
            'createdAt' => $this->created_at,
        ];

        // Include admin permissions if user is ADMIN
        if ($this->role === 'ADMIN' && $this->adminProfile) {
            $adminProfile = $this->adminProfile;
            
            // Ensure permissions is always an object
            $permissions = $adminProfile->permissions;
            if (is_string($permissions)) {
                $permissions = json_decode($permissions, true) ?? [];
            }
            if (!is_array($permissions)) {
                $permissions = [];
            }
            
            $data['permissions'] = $permissions;
            $data['isScout'] = (bool)($adminProfile->is_scout ?? false);
            $data['scoutedPlayersCount'] = (int)($adminProfile->scouted_players_count ?? 0);
        }

        return $data;
    }
}
