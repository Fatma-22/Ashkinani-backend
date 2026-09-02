<?php

namespace App\Http\Requests\Meeting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMeetingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'meeting_type' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'string', 'max:255'],
            'fees' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'meeting_date' => ['sometimes', 'date'],
            'meeting_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'related_person_type' => ['nullable', 'string', 'in:player,coach,other'],
            'related_person_name' => ['nullable', 'string', 'max:255'],
            'player_id' => ['nullable', 'exists:players,id'],
            'status' => ['nullable', 'string', 'in:SCHEDULED,COMPLETED,CANCELLED'],
        ];
    }
}
