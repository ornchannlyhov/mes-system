<?php

namespace App\Http\Requests\Engineering;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBomOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'work_center_id' => 'sometimes|exists:work_centers,id',
            'duration_minutes' => 'sometimes|numeric|min:0',
            'sequence' => 'nullable|integer|min:0',
            'needs_quality_check' => 'boolean',
            'instruction_file_url' => 'nullable|string',
        ];
    }
}
