<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingProgramResource extends JsonResource
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
            'created_by' => $this->created_by,
            'title' => $this->title,
            'workout_plan' => $this->workout_plan,
            'recovery_plan' => $this->recovery_plan,
            'start_date' => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'end_date' => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'is_active' => (bool) $this->is_active,
            'file_url' => $this->file_url ? url($this->file_url) : null,
            'image_url' => $this->image_url ? url($this->image_url) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'player' => new PlayerResource($this->whenLoaded('player')),
            'creator' => new UserResource($this->whenLoaded('creator')),
        ];
    }
}
