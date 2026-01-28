<?php

namespace App\Http\Controllers\Api\Execution;

use App\Http\Controllers\Controller;
use App\Http\Requests\Execution\StoreScrapRequest;
use App\Http\Requests\Execution\UpdateScrapRequest;
use App\Models\Scrap;
use App\Models\Location;
use App\Services\StockService;
use Illuminate\Http\Request;

class ScrapController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {
    }

    public function index(Request $request)
    {
        $query = Scrap::with(['product', 'manufacturingOrder', 'workOrder', 'reporter', 'location']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 10)));
    }

    public function store(StoreScrapRequest $request)
    {
        $this->authorize('create', Scrap::class);

        $validated = $request->validated();
        $validated['reported_by'] = $request->user()->id;

        // DB Transaction for data consistency
        return \DB::transaction(function () use ($validated) {
            // 1. Create Scrap Record
            $scrap = Scrap::create($validated);

            // 2. Deduct from Inventory (if location is provided)
            // Note: The request validation ensures location_id exists
            $location = Location::findOrFail($validated['location_id']);

            $this->stockService->adjust($location, [
                'product_id' => $validated['product_id'],
                'lot_id' => $validated['lot_id'] ?? null,
                'quantity' => $validated['quantity'],
                'type' => 'subtract',
            ]);

            // 3. Record Scrap Cost
            $product = \App\Models\Product::find($validated['product_id']);
            $cost = $product->cost ?? 0;

            \App\Models\CostEntry::create([
                'manufacturing_order_id' => $validated['manufacturing_order_id'] ?? null,
                'product_id' => $validated['product_id'],
                'work_order_id' => $validated['work_order_id'] ?? null,
                'cost_type' => 'scrap', // Distinct type for reporting
                'quantity' => $validated['quantity'],
                'unit_cost' => $cost,
                'total_cost' => $cost * $validated['quantity'],
                'notes' => 'Scrap: ' . ($validated['reason'] ?? 'No reason provided'),
                'scrap_id' => $scrap->id // Link to source
            ]);

            return response()->json($scrap->load(['product', 'reporter']), 201);
        });
    }

    public function show(Scrap $scrap)
    {
        $this->authorize('view', $scrap);
        return response()->json($scrap->load(['product', 'manufacturingOrder', 'workOrder', 'reporter']));
    }

    // Update and Destroy might be restricted in real-life (accounting impact), but adding stubs for API resource compatibility
    public function update(UpdateScrapRequest $request, Scrap $scrap)
    {
        $this->authorize('update', $scrap);

        $validated = $request->validated();

        $scrap->update($validated);

        // Auto-Adjust CostEntry
        $costEntry = \App\Models\CostEntry::where('scrap_id', $scrap->id)->first();
        if ($costEntry) {
            $product = $scrap->product;
            $cost = $product->cost ?? 0;
            $newQuantity = $validated['quantity'] ?? $scrap->quantity; // Use updated or existing

            $costEntry->update([
                'quantity' => $newQuantity,
                'total_cost' => $cost * $newQuantity,
                'notes' => 'Scrap (Updated): ' . ($validated['reason'] ?? $scrap->reason)
            ]);
        }
    }

    public function destroy(Scrap $scrap)
    {
        $this->authorize('delete', $scrap);

        $scrap->delete();
        return response()->json(null, 204);
    }
}
