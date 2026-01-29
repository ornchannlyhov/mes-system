<?php

namespace App\Http\Requests\Engineering;

use Illuminate\Foundation\Http\FormRequest;

class StoreBomOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'work_center_id' => 'required|exists:work_centers,id',
            'duration_minutes' => 'required|numeric|min:0',
            'sequence' => 'nullable|integer|min:0',
            'needs_quality_check' => 'boolean',
            'instruction_file_url' => 'nullable|string',
        ];
    }
}
