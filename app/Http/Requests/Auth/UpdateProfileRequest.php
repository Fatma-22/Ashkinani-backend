<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $this->user()->id,
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048|nullable',
            'organization' => 'sometimes|nullable|string|max:255',
            'phone' => 'sometimes|nullable|string|max:255|unique:users,phone,' . $this->user()->id,
            'country' => 'sometimes|nullable|string|max:255',
            'current_password' => 'required_with:new_password|current_password',
            'new_password' => 'sometimes|string|min:8|confirmed',
        ];
    }
}
