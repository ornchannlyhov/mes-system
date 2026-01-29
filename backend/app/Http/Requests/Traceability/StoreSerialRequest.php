<?php

namespace App\Http\Requests\Traceability;

use Illuminate\Foundation\Http\FormRequest;

class StoreSerialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:serials',
            'product_id' => 'required|exists:products,id',
            'lot_id' => 'nullable|exists:lots,id',
            'manufacturing_order_id' => 'nullable|exists:manufacturing_orders,id',
            'component_serials' => 'nullable|array',
        ];
    }
}
