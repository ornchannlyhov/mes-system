<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Services\StockService;
use App\Http\Requests\Inventory\StoreStockTransferRequest;
use App\Http\Requests\Inventory\StoreStockReserveRequest;
use App\Http\Requests\Inventory\StoreStockReleaseRequest;

class StockMovementController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {
    }

    public function transfer(StoreStockTransferRequest $request)
    {
        try {
            $result = $this->stockService->transfer($request->validated());
            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function reserve(StoreStockReserveRequest $request)
    {
        $validated = $request->validated();

        try {
            $stock = $this->stockService->reserve(
                $validated['product_id'],
                $validated['location_id'],
                $validated['quantity'],
                $validated['lot_id'] ?? null
            );
            return response()->json($stock);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function release(StoreStockReleaseRequest $request)
    {
        $validated = $request->validated();

        $stock = $this->stockService->release(
            $validated['product_id'],
            $validated['location_id'],
            $validated['quantity'],
            $validated['lot_id'] ?? null
        );

        return response()->json($stock);
    }
}
