<?php

namespace App\Http\Requests\Engineering;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('products')->where(function ($query) {
                    return $query->where('organization_id', $this->user()->organization_id);
                })
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|in:raw,component,finished',
            'tracking' => 'required|in:none,lot,serial',
            'uom' => 'required|string|max:10',
            'cost' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
    }
}
