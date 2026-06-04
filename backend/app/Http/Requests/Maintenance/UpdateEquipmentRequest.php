<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipmentRequest extends FormRequest
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
                Rule::unique('equipment')->ignore($this->route('equipment'))->where(function ($query) {
                    return $query->where('organization_id', $this->user()->organization_id)
                                 ->whereNull('deleted_at');
                }),
            ],
            'work_center_id' => 'nullable|exists:work_centers,id',
            'maintenance_interval_days' => 'nullable|integer|min:1',
            'status' => 'in:operational,maintenance,broken',
            'notes' => 'nullable|string',
        ];
    }
}
