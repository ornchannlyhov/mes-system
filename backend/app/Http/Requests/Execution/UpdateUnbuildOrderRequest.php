<?php

namespace App\Http\Requests\Execution;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnbuildOrderRequest extends FormRequest
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
            'quantity' => 'sometimes|numeric|min:1',
            'reason' => 'nullable|string',
            'product_id' => 'sometimes|exists:products,id',
        ];
    }
}
