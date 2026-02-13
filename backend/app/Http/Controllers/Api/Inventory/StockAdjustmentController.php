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
                ['reference', 'notes'], // Searchable fields
                ['reason', 'product_id', 'location_id', 'lot_id'] // Exact filters
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
        // Only allow updating reference and notes, not quantities or product/location
        // This preserves the audit trail integrity
        $stockAdjustment->update([
            'reference' => $request->input('reference'),
            'notes' => $request->input('notes'),
        ]);

        return $this->success(
            $stockAdjustment->load(['product', 'location', 'lot', 'user']),
            ['message' => 'Stock adjustment updated successfully']
        );
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
