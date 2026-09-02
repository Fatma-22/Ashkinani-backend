<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'country' => $this->country,
            'logo_url' => $this->logo_url,
            // When loaded, we can include the players count or a summary
            'players_count' => $this->whenCounted('players'),
        ];
    }
}
