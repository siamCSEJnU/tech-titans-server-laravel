<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserLoginUpdateRequest extends FormRequest
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
        // $userId = auth()->id();
        return [
            'name' => 'string|max:255',
            'email' => '|string|email',
            'mobile_number' => 'sometimes|digits:11|regex:/^01[3-9]\d{8}$/|unique:users,mobile_number',
            'password' => 'string|min:8',
            'location' => 'string|max:255',
            'country' => 'string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'type' => 'string|max:255'
        ];
    }
}
