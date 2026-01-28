<?php

namespace App\Http\Requests\Engineering;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkCenterRequest extends FormRequest
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
                Rule::unique('work_centers', 'code')->ignore($this->work_center),
            ],
            'location' => 'nullable|string|max:255',
            'cost_per_hour' => 'nullable|numeric|min:0',
            'overhead_per_hour' => 'nullable|numeric|min:0',
            'efficiency_percent' => 'nullable|numeric|min:0|max:200',
            'status' => 'in:active,maintenance,down',
        ];
    }
}
