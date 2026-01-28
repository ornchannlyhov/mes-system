<?php

namespace App\Http\Requests\Execution;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'manufacturing_order_id' => 'required|exists:manufacturing_orders,id',
            'operation_id' => 'required|exists:operations,id',
            'work_center_id' => 'required|exists:work_centers,id',
            'assigned_to' => 'nullable|exists:users,id',
            'sequence' => 'nullable|integer',
            'duration_expected' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }
}
