<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'club_name' => $this->club_name,
            'club_name_ar' => $this->club_name_ar,
            'club_country' => $this->club_country,
            'club_country_ar' => $this->club_country_ar,
            'start_date' => $this->start_date ? $this->start_date->toDateString() : null,
            'end_date' => $this->end_date ? $this->end_date->toDateString() : null,
            'file_url' => $this->file_url ? (
                (str_starts_with($this->file_url, 'data:') || filter_var($this->file_url, FILTER_VALIDATE_URL)) 
                    ? $this->file_url 
                    : asset('storage/' . $this->file_url)
            ) : null,
            'notes' => $this->notes,
            'notes_ar' => $this->notes_ar,
        ];
    }
}
