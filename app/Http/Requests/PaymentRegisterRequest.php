<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRegisterRequest extends FormRequest
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
    public function rules()
    {
        return [
            'total_amount' => 'required|numeric|min:0.01',
            'transaction_id' => 'required|string|unique:payments,transaction_id',
            'mobile_number' => 'required|exists:users,mobile_number',
            'email' => 'required|exists:users,email',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.name' => 'required|string',
            'products.*.price' => 'required|numeric|min:0.01',
            'products.*.quantity' => 'required|integer|min:1',
        ];
    }
}
