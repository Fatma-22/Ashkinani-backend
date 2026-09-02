<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,
            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,
            'code' => $this->code,
            'image_url' => $this->image_path ? (filter_var($this->image_path, FILTER_VALIDATE_URL) ? $this->image_path : asset('storage/' . $this->image_path)) : null,
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'is_active' => $this->is_active,
            'sponsor' => $this->sponsor ? [
                'id' => $this->sponsor->id,
                'name_en' => $this->sponsor->name_en,
                'name_ar' => $this->sponsor->name_ar,
                'logo_url' => $this->sponsor->logo_path ? (filter_var($this->sponsor->logo_path, FILTER_VALIDATE_URL) ? $this->sponsor->logo_path : asset('storage/' . $this->sponsor->logo_path)) : null,
            ] : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
