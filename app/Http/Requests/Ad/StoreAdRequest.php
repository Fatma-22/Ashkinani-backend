<?php

namespace App\Http\Requests\Ad;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdRequest extends FormRequest
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
            'image' => 'nullable|image|max:5120',
            'click_url' => 'nullable|url|max:255',
            'type' => 'nullable|string|in:BANNER,POPUP',
            'is_active' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ];
    }
}
