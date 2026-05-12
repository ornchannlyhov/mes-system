<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Inventory\StoreStockAdjustmentRequest;
use App\Models\StockAdjustment;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockAdjustmentController extends BaseController
{
    public function __construct(
        protected StockService $stockService
    ) {
    }

    /**
     * Display a listing of stock adjustments.
     */
    public function index(Request $request)
    {
        $query = StockAdjustment::select(['id', 'product_id', 'location_id', 'lot_id', 'user_id', 'quantity', 'reason', 'reference', 'notes', 'created_at'])
            ->with(['product:id,name,code', 'location:id,name,code', 'lot:id,name', 'user:id,name'])
            ->applyStandardFilters(
                $request,
                ['reference', 'notes'], 
                ['reason', 'product_id', 'location_id', 'lot_id']
            );

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $counts = $this->getStatusCounts(StockAdjustment::query(), 'reason');

        return $this->respondWithPagination(
            $query->paginate($request->get('per_page', 10)),
            ['counts' => $counts]
        );
    }

    /**
     * Store a newly created stock adjustment.
     */
    public function store(StoreStockAdjustmentRequest $request)
    {
        try {
            $stock = $this->stockService->adjustStock($request->validated());

            return $this->success($stock, ['message' => 'Stock adjusted successfully'], 201);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Display the specified stock adjustment.
     */
    public function show(StockAdjustment $stockAdjustment)
    {
        return $this->success(
            $stockAdjustment->load(['product', 'location', 'lot', 'user'])
        );
    }

    /**
     * Update the specified stock adjustment.
     */
    public function update(StoreStockAdjustmentRequest $request, StockAdjustment $stockAdjustment)
    {
        try {
            return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $stockAdjustment) {
                // 1. Capture old state to revert it
                $oldProductId = $stockAdjustment->product_id;
                $oldLocationId = $stockAdjustment->location_id;
                $oldLotId = $stockAdjustment->lot_id;
                $oldQty = $stockAdjustment->quantity;

                // 2. Update the record with new data
                $stockAdjustment->update($request->validated());

                // 3. Revert Old Effect from Stock
                $oldStock = \App\Models\Stock::where([
                    'product_id' => $oldProductId,
                    'location_id' => $oldLocationId,
                    'lot_id' => $oldLotId,
                ])->first();

                if ($oldStock) {
                    $oldStock->quantity -= $oldQty;
                    $oldStock->save();
                }

                // 4. Apply New Effect to Stock
                $newStock = \App\Models\Stock::firstOrNew([
                    'product_id' => $stockAdjustment->product_id,
                    'location_id' => $stockAdjustment->location_id,
                    'lot_id' => $stockAdjustment->lot_id,
                ]);

                if (!$newStock->exists) {
                    $newStock->organization_id = $stockAdjustment->organization_id;
                }

                $newStock->quantity += $stockAdjustment->quantity;

                if ($newStock->quantity < 0) {
                    throw new \InvalidArgumentException('Adjustment update would result in negative stock (' . $newStock->quantity . ')');
                }

                $newStock->save();

                return $this->success(
                    $stockAdjustment->load(['product', 'location', 'lot', 'user']),
                    ['message' => 'Stock adjustment updated and inventory synchronized successfully']
                );
            });
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->error('Failed to update stock adjustment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified stock adjustment (admin only).
     */
    public function destroy(StockAdjustment $stockAdjustment)
    {
        // 1. Prevent deletion of manufacturing usage
        if (in_array($stockAdjustment->reason, ['manufacturing_consumption', 'manufacturing_production'])) {
            return $this->error('Cannot delete system-generated manufacturing adjustments.', 403);
        }

        // 2. Revert Stock & Delete
        \Illuminate\Support\Facades\DB::transaction(function () use ($stockAdjustment) {
            $stock = \App\Models\Stock::where('product_id', $stockAdjustment->product_id)
                ->where('location_id', $stockAdjustment->location_id)
                ->where('lot_id', $stockAdjustment->lot_id)
                ->first();

            if ($stock) {
                $stock->quantity -= $stockAdjustment->quantity;
                $stock->save();
            }

            $stockAdjustment->delete();
        });

        return $this->success(null, ['message' => 'Stock adjustment deleted and stock reverted'], 204);
    }
}
