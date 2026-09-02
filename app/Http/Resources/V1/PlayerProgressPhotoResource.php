<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PlayerProgressPhotoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'player_id' => $this->player_id,
            'photo_url' => asset($this->photo_url),
            'view_type' => $this->view_type,
            'stage' => $this->stage,
            'captured_at' => $this->captured_at ? $this->captured_at->format('Y-m-d') : null,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
