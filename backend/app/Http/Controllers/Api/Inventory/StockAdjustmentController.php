<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreStockAdjustmentRequest;
use App\Models\StockAdjustment;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockAdjustmentController extends Controller
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
        $query = StockAdjustment::with(['product', 'location', 'lot', 'user'])
            ->orderBy('created_at', 'desc');

        // Filter by reason if provided
        if ($request->has('reason')) {
            $query->where('reason', $request->reason);
        }

        // Filter by product if provided
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by location if provided
        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // Filter by lot if provided
        if ($request->has('lot_id')) {
            $query->where('lot_id', $request->lot_id);
        }

        // Filter by date range if provided
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return response()->json([
            'data' => $query->get()
        ]);
    }

    /**
     * Store a newly created stock adjustment.
     */
    public function store(StoreStockAdjustmentRequest $request)
    {
        try {
            $stock = $this->stockService->adjustStock($request->validated());

            return response()->json([
                'message' => 'Stock adjusted successfully',
                'data' => $stock
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified stock adjustment.
     */
    public function show(StockAdjustment $stockAdjustment)
    {
        return response()->json([
            'data' => $stockAdjustment->load(['product', 'location', 'lot', 'user'])
        ]);
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

        return response()->json([
            'message' => 'Stock adjustment updated successfully',
            'data' => $stockAdjustment->load(['product', 'location', 'lot', 'user'])
        ]);
    }

    /**
     * Remove the specified stock adjustment (admin only).
     */
    public function destroy(StockAdjustment $stockAdjustment)
    {
        // Note: Deleting adjustments is generally not recommended as it removes audit trail
        // Consider implementing soft deletes or requiring special permissions
        $stockAdjustment->delete();

        return response()->json([
            'message' => 'Stock adjustment deleted'
        ]);
    }
}
