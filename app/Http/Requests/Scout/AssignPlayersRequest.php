<?php

namespace App\Http\Requests\Scout;

use Illuminate\Foundation\Http\FormRequest;

class AssignPlayersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Admin middleware handles authorization, but any authenticated admin 
        // with canManageScouts (checked in route/controller) should be able 
        // to assign players if they are authorized for scout management.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'player_ids' => ['required', 'array'],
            'player_ids.*' => ['required', 'exists:players,id'],
        ];
    }
}
