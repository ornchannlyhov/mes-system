<?php

namespace App\Http\Requests\Execution;

use Illuminate\Foundation\Http\FormRequest;

class CompleteManufacturingOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qty_produced' => 'sometimes|numeric|min:0',
            'location_id' => 'sometimes|exists:locations,id',
            'consumptions' => 'sometimes|array',
            'consumptions.*.id' => 'required|exists:consumptions,id',
            'consumptions.*.qty_consumed' => 'required|numeric|min:0',
        ];
    }
}
