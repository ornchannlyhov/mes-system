<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Api\BaseController;
use App\Models\Stock;
use Illuminate\Http\Request;

class StockController extends BaseController
{
    public function index(Request $request)
    {
        $query = Stock::with(['product', 'location', 'lot'])
            ->applyStandardFilters(
                $request,
                [], // No direct text fields on stock to search
                ['location_id', 'product_id', 'lot_id'] // Exact filters
            );

        if ($request->has('search') && $request->search) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($q) use ($search) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(code) LIKE ?', ["%{$search}%"]);
                })->orWhereHas('location', function ($q) use ($search) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                });
            });
        }

        return $this->respondWithPagination(
            $query->paginate($request->get('per_page', 10))
        );
    }

    public function show(Stock $stock)
    {
        return $this->success(
            $stock->load(['product', 'location', 'lot'])
        );
    }
}
