<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerPhysicalReportResource extends JsonResource
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
            'weight' => $this->weight,
            'height' => $this->height,
            'fat_percentage' => $this->fat_percentage,
            'muscle_mass' => $this->muscle_mass,
            'body_mass_index' => $this->body_mass_index,
            'physical_assessment' => $this->physical_assessment,
            'report_date' => $this->report_date ? $this->report_date->format('Y-m-d') : null,
            'additional_metrics' => $this->additional_metrics,
            'file_url' => $this->file_url ? url($this->file_url) : null,
            'image_url' => $this->image_url ? url($this->image_url) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships if loaded
            'player' => new PlayerResource($this->whenLoaded('player')),
            'creator' => new UserResource($this->whenLoaded('creator')),
        ];
    }
}
