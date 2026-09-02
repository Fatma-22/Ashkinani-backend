<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,
            'content_en' => $this->content_en,
            'content_ar' => $this->content_ar,
            'category_en' => $this->category_en,
            'category_ar' => $this->category_ar,
            'main_image_url' => $this->main_image_path ? (filter_var($this->main_image_path, FILTER_VALIDATE_URL) ? $this->main_image_path : asset('storage/' . $this->main_image_path)) : null,
            'gallery_image_urls' => collect($this->gallery_images)->map(fn($path) => filter_var($path, FILTER_VALIDATE_URL) ? $path : asset('storage/' . $path)),
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
