<?php

namespace App\Http\Requests\Traceability;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSerialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $serialId = $this->route('serial') ? $this->route('serial')->id : null;
        return [
            'name' => 'sometimes|string|max:100|unique:serials,name,' . $serialId,
            'lot_id' => 'nullable|exists:lots,id',
            'component_serials' => 'nullable|array',
        ];
    }
}
