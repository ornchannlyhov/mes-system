<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'equipment_id' => 'required|exists:equipment,id',
            'request_type' => 'required|in:preventive,corrective',
            'priority' => 'in:low,normal,high,critical',
            'status' => 'in:draft,confirmed,in_progress,done,cancelled',
            'scheduled_date' => 'nullable|date',
            'description' => 'required|string',
            'diagnosis' => 'nullable|string',
            'actions_taken' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ];
    }
}
