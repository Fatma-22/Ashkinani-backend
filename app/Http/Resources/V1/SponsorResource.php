<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SponsorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'services_en' => $this->services_en,
            'services_ar' => $this->services_ar,
            'logo_url' => $this->logo_path ? (filter_var($this->logo_path, FILTER_VALIDATE_URL) ? $this->logo_path : asset('storage/' . $this->logo_path)) : null,
            'contract_url' => $this->contract_path ? (filter_var($this->contract_path, FILTER_VALIDATE_URL) ? $this->contract_path : asset('storage/' . $this->contract_path)) : null,
            'website_url' => $this->website_url,
            'type' => $this->type,
            'tier' => $this->tier,
            'start_date' => $this->start_date ? $this->start_date->toDateString() : null,
            'end_date' => $this->end_date ? $this->end_date->toDateString() : null,
            'agreement_text' => $this->agreement_text,
            'agreement_text_ar' => $this->agreement_text_ar,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'images' => SponsorImageResource::collection($this->whenLoaded('images')),
            'discounts' => DiscountResource::collection($this->whenLoaded('discounts')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
