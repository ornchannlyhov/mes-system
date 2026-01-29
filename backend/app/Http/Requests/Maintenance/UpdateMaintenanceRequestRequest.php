<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'equipment_id' => 'sometimes|exists:equipment,id',
            'request_type' => 'sometimes|in:preventive,corrective',
            'priority' => 'sometimes|in:low,normal,high,critical',
            'status' => 'sometimes|in:draft,confirmed,in_progress,done,cancelled',
            'scheduled_date' => 'nullable|date',
            'description' => 'sometimes|string',
            'diagnosis' => 'nullable|string',
            'actions_taken' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'actual_start' => 'nullable|date',
            'actual_end' => 'nullable|date|after:actual_start',
            'parts_cost' => 'nullable|numeric|min:0',
            'labor_cost' => 'nullable|numeric|min:0',
        ];
    }
}
