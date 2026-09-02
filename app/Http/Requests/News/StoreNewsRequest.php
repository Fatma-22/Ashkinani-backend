<?php

namespace App\Http\Requests\News;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title_en' => 'nullable|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'content_en' => 'nullable|string',
            'content_ar' => 'nullable|string',
            'category_en' => 'nullable|string|max:100',
            'category_ar' => 'nullable|string|max:100',
            'main_image' => 'nullable|image|max:5120',
            'gallery.*' => 'nullable|image|max:5120',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'published_at' => 'nullable|date'
        ];
    }
}
