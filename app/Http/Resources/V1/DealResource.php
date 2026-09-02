<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
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
            'playerId' => $this->player_id,
            'manualPlayerName' => $this->manual_player_name,
            'manualPlayerNameAr' => $this->manual_player_name_ar,
            'manualPlayerRole' => $this->manual_player_role,
            'manualPlayerSport' => $this->manual_player_sport,
            'fromClub' => $this->from_club,
            'fromClubAr' => $this->from_club_ar,
            'toClub' => $this->to_club,
            'toClubAr' => $this->to_club_ar,
            'dealDate' => $this->deal_date 
                ? ($this->deal_date instanceof \DateTimeInterface 
                    ? $this->deal_date->format('Y-m-d') 
                    : \Illuminate\Support\Carbon::parse($this->deal_date)->format('Y-m-d')) 
                : null,
            'contractStartDate' => $this->when(auth()->check() && in_array(auth()->user()->role, ['ADMIN', 'OWNER', 'AGENT']), $this->contract_start_date ? \Illuminate\Support\Carbon::parse($this->contract_start_date)->format('Y-m-d') : null),
            'contractEndDate' => $this->when(auth()->check() && in_array(auth()->user()->role, ['ADMIN', 'OWNER', 'AGENT']), $this->contract_end_date ? \Illuminate\Support\Carbon::parse($this->contract_end_date)->format('Y-m-d') : null),
            'contractUrl' => $this->when(auth()->check() && in_array(auth()->user()->role, ['ADMIN', 'OWNER', 'AGENT']), $this->contract_url),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'type' => $this->type,
            'notes' => $this->notes,
            'player' => new PlayerResource($this->whenLoaded('player')),
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
