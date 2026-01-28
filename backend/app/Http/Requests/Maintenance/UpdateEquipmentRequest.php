<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $equipmentId = optional($this->route('equipment'))->id ?? $this->route('equipment');
        return [
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:50|unique:equipment,code,' . $equipmentId,
            'work_center_id' => 'nullable|exists:work_centers,id',
            'maintenance_interval_days' => 'nullable|integer|min:1',
            'status' => 'in:operational,maintenance,broken',
            'notes' => 'nullable|string',
        ];
    }
}
