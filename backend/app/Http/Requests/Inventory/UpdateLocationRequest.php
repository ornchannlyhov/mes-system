<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('locations')->ignore($this->route('location'))->where(function ($query) {
                    return $query->where('organization_id', $this->user()->organization_id)
                                 ->whereNull('deleted_at');
                }),
            ],
            'type' => 'in:warehouse,production,scrap',
        ];
    }
}
