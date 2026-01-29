<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Api\BaseController;
use App\Services\StockService;
use App\Http\Requests\Inventory\StoreStockTransferRequest;
use App\Http\Requests\Inventory\StoreStockReserveRequest;
use App\Http\Requests\Inventory\StoreStockReleaseRequest;

class StockMovementController extends BaseController
{
    public function __construct(
        protected StockService $stockService
    ) {
    }

    public function transfer(StoreStockTransferRequest $request)
    {
        try {
            $result = $this->stockService->transfer($request->validated());
            return $this->success($result, ['message' => 'Stock transferred successfully']);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
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
            return $this->success($stock, ['message' => 'Stock reserved successfully']);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function release(StoreStockReleaseRequest $request)
    {
        $validated = $request->validated();

        try {
            $stock = $this->stockService->release(
                $validated['product_id'],
                $validated['location_id'],
                $validated['quantity'],
                $validated['lot_id'] ?? null
            );
            return $this->success($stock, ['message' => 'Stock released successfully']);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
