<?php

namespace App\Http\Requests\Execution;

use Illuminate\Foundation\Http\FormRequest;

class UpdateManufacturingOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qty_to_produce' => 'sometimes|numeric|min:0.0001',
            'priority' => 'in:low,normal,high,urgent',
            'scheduled_start' => 'nullable|date',
            'scheduled_end' => 'nullable|date|after_or_equal:scheduled_start',
            'notes' => 'nullable|string',
        ];
    }
}
