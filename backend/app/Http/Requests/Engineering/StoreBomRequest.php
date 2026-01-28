<?php

namespace App\Http\Requests\Engineering;

use Illuminate\Foundation\Http\FormRequest;

class StoreBomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'type' => 'in:normal,phantom',
            'qty_produced' => 'required|numeric|min:0.0001',
            'is_active' => 'boolean',
            'lines' => 'nullable|array',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.sequence' => 'nullable|integer',
            'operations' => 'nullable|array',
            'operations.*.work_center_id' => 'required|exists:work_centers,id',
            'operations.*.name' => 'required|string',
            'operations.*.sequence' => 'nullable|integer',
            'operations.*.duration_minutes' => 'nullable|numeric|min:0',
            'operations.*.needs_quality_check' => 'boolean',
            'operations.*.instruction_file_url' => 'nullable|string',
            'operations.*.quality_checks' => 'nullable|array',
            'operations.*.quality_checks.*.id' => 'nullable|integer',
            'operations.*.quality_checks.*.type' => 'required|in:pass_fail,measurement',
            'operations.*.quality_checks.*.label' => 'required|string',
            'operations.*.quality_checks.*.description' => 'nullable|string',
            'operations.*.quality_checks.*.instructions' => 'nullable|string',
            'operations.*.quality_checks.*.min_value' => 'nullable|numeric',
            'operations.*.quality_checks.*.max_value' => 'nullable|numeric',
        ];
    }
}
