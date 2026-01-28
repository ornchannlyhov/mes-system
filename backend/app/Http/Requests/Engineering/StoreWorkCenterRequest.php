<?php

namespace App\Http\Requests\Engineering;

use Illuminate\Foundation\Http\FormRequest;

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
            'code' => 'required|string|max:50|unique:work_centers',
            'location' => 'nullable|string|max:255',
            'cost_per_hour' => 'nullable|numeric|min:0',
            'overhead_per_hour' => 'nullable|numeric|min:0',
            'efficiency_percent' => 'nullable|numeric|min:0|max:200',
            'status' => 'in:active,inactive,maintenance,down',
        ];
    }
}

