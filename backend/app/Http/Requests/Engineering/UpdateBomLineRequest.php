<?php

namespace App\Http\Requests\Engineering;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBomLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'sometimes|exists:products,id',
            'quantity' => 'sometimes|numeric|min:0.0001',
            'sequence' => 'nullable|integer|min:0',
        ];
    }
}
