<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'equipment_id' => 'required|exists:equipment,id',
            'name' => 'required|string|max:255',
            'trigger_type' => 'required|in:time,cycles',
            'interval_days' => 'nullable|integer|min:1',
            'interval_cycles' => 'nullable|integer|min:1',
            'last_maintenance' => 'nullable|date',
            'next_maintenance' => 'nullable|date',
            'instructions' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
