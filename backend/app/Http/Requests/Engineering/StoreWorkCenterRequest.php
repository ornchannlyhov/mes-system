<?php

namespace App\Http\Requests\Engineering;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('work_centers')->where(function ($query) {
                    return $query->where('organization_id', $this->user()->organization_id)
                                 ->whereNull('deleted_at');
                }),
            ],
            'location' => 'nullable|string|max:255',
            'cost_per_hour' => 'nullable|numeric|min:0',
            'overhead_per_hour' => 'nullable|numeric|min:0',
            'efficiency_percent' => 'nullable|numeric|min:0|max:200',
            'status' => 'in:active,inactive,maintenance,down',
        ];
    }
}
