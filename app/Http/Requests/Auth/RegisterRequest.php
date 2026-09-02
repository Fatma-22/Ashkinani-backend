<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['nullable', 'string', 'in:AGENT,PLAYER,PUBLIC'], 
            'phone' => ['required', 'string', 'unique:users'],
            'country' => ['required', 'string'],
            'member_type' => ['required', 'string', 'in:PLAYER,COACH,SCOUT,CLUB,PHOTOGRAPHER,REFEREE,ADMINISTRATOR,DESIGNER,OTHER'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'organization' => ['required', 'string', 'max:255'],
        ];
    }
}
