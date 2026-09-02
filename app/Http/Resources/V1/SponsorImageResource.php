<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SponsorImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'image_url' => $this->image_path ? (filter_var($this->image_path, FILTER_VALIDATE_URL) ? $this->image_path : asset('storage/' . $this->image_path)) : null,
            'sort_order' => $this->sort_order,
        ];
    }
}
