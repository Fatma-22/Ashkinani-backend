<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nameAr' => $this->name_ar,
            'email' => $this->when(in_array($request->user()?->role, ['OWNER', 'ADMIN']), $this->email),
            'phone' => $this->when(in_array($request->user()?->role, ['OWNER', 'ADMIN']), $this->phone),
            'company' => $this->company,
            'companyAr' => $this->company_ar,
            'avatar' => $this->avatar ? (filter_var($this->avatar, FILTER_VALIDATE_URL) ? $this->avatar : asset('storage/' . $this->avatar)) : null,

            'assignedPlayerIds' => $this->players->pluck('id'),
            'user' => new UserResource($this->whenLoaded('user')),
            'players' => PlayerResource::collection($this->whenLoaded('players')),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
