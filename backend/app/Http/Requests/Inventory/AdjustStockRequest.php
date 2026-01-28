<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'lot_id' => 'nullable|exists:lots,id',
            'quantity' => 'required|numeric',
            'type' => 'required|in:set,add,subtract',
        ];
    }
}
