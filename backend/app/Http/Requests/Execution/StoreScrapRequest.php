<?php

namespace App\Http\Requests\Execution;

use Illuminate\Foundation\Http\FormRequest;

class StoreScrapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.0001',
            'reason' => 'nullable|string',
            'location_id' => 'required|exists:locations,id', // Source location to deduct from
            'lot_id' => 'nullable|exists:lots,id',
            'manufacturing_order_id' => 'nullable|exists:manufacturing_orders,id',
            'work_order_id' => 'nullable|exists:work_orders,id',
        ];
    }
}
