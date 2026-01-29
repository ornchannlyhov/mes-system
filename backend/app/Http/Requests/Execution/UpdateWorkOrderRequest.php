<?php

namespace App\Http\Requests\Execution;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:pending,ready,in_progress,blocked,done',
            'qa_status' => 'sometimes|in:pending,pass,fail',
            'qa_comments' => 'nullable|string',
        ];
    }
}
