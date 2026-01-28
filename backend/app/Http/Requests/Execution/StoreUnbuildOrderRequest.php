<?php

namespace App\Http\Requests\Execution;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnbuildOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'bom_id' => 'required|exists:boms,id',
            'quantity' => 'required|numeric|min:0.0001',
            'manufacturing_order_id' => 'nullable|exists:manufacturing_orders,id',
            'serial_number_id' => 'nullable|exists:serials,id',
        ];
    }
}
