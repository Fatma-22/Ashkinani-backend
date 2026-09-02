<?php

namespace App\Http\Requests\Sponsor;

use Illuminate\Foundation\Http\FormRequest;

class StoreSponsorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_en' => 'nullable|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'services_en' => 'nullable|string',
            'services_ar' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'contract' => 'nullable|mimes:pdf,doc,docx|max:51200',
            'website_url' => 'nullable|url|max:255',
            'type' => 'nullable|in:SPONSOR,PARTNER',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'agreement_text' => 'nullable|string',
            'agreement_text_ar' => 'nullable|string',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'tier' => 'nullable|string',
            'gallery' => 'nullable|array',
            'gallery.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ];
    }
}
