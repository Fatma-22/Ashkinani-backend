<?php

namespace App\Http\Requests\Deal;

use Illuminate\Foundation\Http\FormRequest;

class StoreDealRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Middleware handles authorization
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'playerId' => 'nullable|exists:players,id',
            'manualPlayerName' => 'nullable|string',
            'manualPlayerNameAr' => 'required_without:playerId|nullable|string',
            'manualPlayerRole' => 'nullable|string',
            'manualPlayerSport' => 'nullable|string',
            'fromClub' => 'nullable|string',
            'fromClubAr' => 'nullable|string',
            'toClub' => 'nullable|string',
            'toClubAr' => 'nullable|string',
            'dealDate' => 'nullable|date',
            'contractStartDate' => 'nullable|date',
            'contractEndDate' => 'nullable|date',
            'contractUrl' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'currency' => 'nullable|string',
            'type' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}
