<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Stock::with(['product', 'location', 'lot']);

        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $stocks = $query->orderBy('id', 'desc')->get();

        return response()->json(['data' => $stocks]);
    }

    public function show(Stock $stock)
    {
        return response()->json([
            'data' => $stock->load(['product', 'location', 'lot'])
        ]);
    }
}
