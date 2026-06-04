<?php

namespace App\Http\Requests\Traceability;

use Illuminate\Foundation\Http\FormRequest;

class StoreLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:lots',
            'product_id' => 'required|exists:products,id',
            'manufactured_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:manufactured_date',
            'initial_qty' => 'nullable|numeric|min:0',
            'location_id' => [
                \Illuminate\Validation\Rule::requiredIf(fn () => (float) $this->input('initial_qty', 0) > 0),
                'nullable',
                'exists:locations,id',
            ],
            'notes' => 'nullable|string',
        ];
    }
}
