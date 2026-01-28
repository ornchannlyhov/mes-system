<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class LogMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'equipment_id' => 'required|exists:equipment,id',
            'type' => 'required|in:preventive,corrective',
            'description' => 'nullable|string',
            'actions_taken' => 'nullable|string',
            'cost' => 'nullable|numeric|min:0',
        ];
    }
}
