<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingResource extends JsonResource
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
            'title' => $this->title,
            'meeting_type' => $this->meeting_type,
            'duration' => $this->duration,
            'fees' => $this->fees,
            'description' => $this->description,
            'meeting_date' => $this->meeting_date ? $this->meeting_date->format('Y-m-d') : null,
            'meeting_time' => $this->meeting_time ? substr($this->meeting_time, 0, 5) : null,
            'location' => $this->location,
            'related_person_type' => $this->related_person_type,
            'related_person_name' => $this->related_person_name,
            'player_id' => $this->player_id,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relations
            'player' => $this->whenLoaded('player', function () {
                return new PlayerResource($this->player); 
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
        ];
    }
}
