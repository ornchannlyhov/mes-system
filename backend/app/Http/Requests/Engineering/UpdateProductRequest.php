<?php

namespace App\Http\Requests\Engineering;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'sometimes',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('products')->ignore($this->route('product'))->where(function ($query) {
                    return $query->where('organization_id', $this->user()->organization_id);
                })
            ],
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:raw,component,finished',
            'tracking' => 'sometimes|in:none,lot,serial',
            'uom' => 'sometimes|string|max:10',
            'cost' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
    }
}
