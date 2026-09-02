<?php

namespace App\Http\Requests\Contract;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'player_id' => ['required', 'exists:players,id'],
            'agent_id' => ['nullable', 'exists:agents,id'],
            'type' => ['required', 'in:PROFESSIONAL,YOUTH,LOAN,AMATEUR'],
            'status' => ['required', 'in:ACTIVE,PENDING,EXPIRED,TERMINATED,NEGOTIATION'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'annual_salary' => ['nullable', 'numeric', 'min:0'],
            'signing_bonus' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:3'],
            'notes' => ['nullable', 'string'],
            'notes_ar' => ['nullable', 'string'],
            'is_visible' => ['boolean'],
        ];
    }
}
