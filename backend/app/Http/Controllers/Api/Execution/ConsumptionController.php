<?php

namespace App\Http\Controllers\Api\Execution;

use App\Http\Controllers\Api\BaseController;
use App\Models\Consumption;
use App\Models\Product;
use App\Models\CostEntry;
use Illuminate\Http\Request;
use App\Http\Requests\Execution\StoreConsumptionRequest;
use App\Http\Requests\Execution\UpdateConsumptionRequest;

use App\Services\StockService;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class ConsumptionController extends BaseController
{
    public function __construct(
        protected StockService $stockService
    ) {
    }
    public function index(Request $request)
    {
        $query = Consumption::with(['product', 'manufacturingOrder'])
            ->applyStandardFilters(
                $request,
                [], // Searchable fields in valid text columns (none explicit here yet)
                ['manufacturing_order_id', 'product_id'] // Filterable
            );

        if ($request->has('has_variance')) {
            $query->whereRaw('qty_consumed != qty_planned');
        }

        if ($request->has('search') && $request->search) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($p) use ($search) {
                    $p->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(code) LIKE ?', ["%{$search}%"]);
                });
            });
        }

        // Counts grouped by manufacturing_order_id doesn't make much sense here, 
        // maybe no specific counts needed unless we group by variance type (over/under).
        // For now, standard pagination.

        return $this->respondWithPagination(
            $query->paginate($request->get('per_page', 20)) // Keep default 20 relevant to existing logic
        );
    }

    public function store(StoreConsumptionRequest $request)
    {
        $this->authorize('create', Consumption::class);

        $validated = $request->validated();

        // Calculate variance and cost impact
        $product = Product::find($validated['product_id']);
        $varianceQty = $validated['qty_consumed'] - $validated['qty_planned'];
        $costImpact = $varianceQty * ($product->cost ?? 0);

        $consumption = Consumption::create([
            'manufacturing_order_id' => $validated['manufacturing_order_id'],
            'product_id' => $validated['product_id'],
            'qty_planned' => $validated['qty_planned'],
            'qty_consumed' => $validated['qty_consumed'],
            'cost_impact' => $costImpact,
        ]);

        // Create CostEntry for material_variance if significant
        // 1. Create Base Material Cost (Planned)
        $materialCost = $validated['qty_planned'] * ($product->cost ?? 0);
        if ($materialCost > 0) {
            CostEntry::create([
                'manufacturing_order_id' => $validated['manufacturing_order_id'],
                'product_id' => $validated['product_id'],
                'cost_type' => 'material',
                'quantity' => $validated['qty_planned'],
                'unit_cost' => $product->cost ?? 0,
                'total_cost' => $materialCost,
                'notes' => 'Material: ' . $product->name,
                'consumption_id' => $consumption->id,
                'created_at' => now(),
            ]);
        }

        // 2. Create Material Variance (Actual - Planned)
        if (abs($costImpact) > 0.0001) {
            CostEntry::create([
                'manufacturing_order_id' => $validated['manufacturing_order_id'],
                'product_id' => $validated['product_id'],
                'cost_type' => 'material_variance',
                'quantity' => $varianceQty,
                'unit_cost' => $product->cost ?? 0,
                'total_cost' => $costImpact,
                'notes' => 'Variance: ' . $product->name . ' (' . ($varianceQty > 0 ? 'Over' : 'Under') . ')',
                'consumption_id' => $consumption->id,
                'created_at' => now(),
            ]);
        }

        return $this->success($consumption->load('product'), [], 201);
    }

    public function update(UpdateConsumptionRequest $request, Consumption $consumption)
    {
        $this->authorize('update', $consumption);

        $validated = $request->validated();

        DB::transaction(function () use ($consumption, $validated) {
            $oldQty = $consumption->qty_consumed;
            $product = $consumption->product;
            $varianceQty = $validated['qty_consumed'] - $validated['qty_planned'];
            $costImpact = $varianceQty * ($product->cost ?? 0);

            // 1. Check if MO is done and adjust Stock
            $mo = $consumption->manufacturingOrder;
            if ($mo && $mo->status === 'done' && $oldQty != $validated['qty_consumed']) {
                $delta = $validated['qty_consumed'] - $oldQty;

                // If location was used for this consumption, update it
                $locationId = $consumption->location_id;
                // If no location saved, we might have an issue tracing where it came from.
                // Assuming consumption has location_id if it was consumed from stock.

                if ($locationId) {
                    $location = Location::find($locationId);
                    if ($location) {
                        if ($delta > 0) {
                            $this->stockService->adjust($location, [
                                'product_id' => $consumption->product_id,
                                'quantity' => $delta,
                                'type' => 'subtract',
                                'lot_id' => $consumption->lot_id,
                                'reason' => 'manufacturing_consumption_update',
                                'reference' => $mo->name,
                                'notes' => 'Adjustment after completion (Consumed More)'
                            ]);
                        } else {
                            $this->stockService->adjust($location, [
                                'product_id' => $consumption->product_id,
                                'quantity' => abs($delta),
                                'type' => 'add', // Revert stock
                                'lot_id' => $consumption->lot_id,
                                'reason' => 'manufacturing_consumption_update',
                                'reference' => $mo->name,
                                'notes' => 'Adjustment after completion (Consumed Less)'
                            ]);
                        }
                    }
                }
            }

            $consumption->update([
                'qty_planned' => $validated['qty_planned'],
                'qty_consumed' => $validated['qty_consumed'],
                'cost_impact' => $costImpact
            ]);

            // 1. Update Base Material Cost
            $materialEntry = CostEntry::where('consumption_id', $consumption->id)
                ->where('cost_type', 'material')
                ->first();

            $materialCost = $validated['qty_planned'] * ($product->cost ?? 0);

            if ($materialEntry) {
                $materialEntry->update([
                    'quantity' => $validated['qty_planned'],
                    'total_cost' => $materialCost,
                ]);
            } elseif ($materialCost > 0) {
                CostEntry::create([
                    'manufacturing_order_id' => $consumption->manufacturing_order_id,
                    'product_id' => $consumption->product_id,
                    'cost_type' => 'material',
                    'quantity' => $validated['qty_planned'],
                    'unit_cost' => $product->cost ?? 0,
                    'total_cost' => $materialCost,
                    'notes' => 'Material: ' . $product->name,
                    'consumption_id' => $consumption->id,
                ]);
            }

            // 2. Update Material Variance
            $varianceEntry = CostEntry::where('consumption_id', $consumption->id)
                ->where('cost_type', 'material_variance')
                ->first();

            if (abs($costImpact) > 0.0001) {
                if ($varianceEntry) {
                    $varianceEntry->update([
                        'quantity' => $varianceQty,
                        'total_cost' => $costImpact,
                        'notes' => 'Variance: ' . $product->name . ' (' . ($varianceQty > 0 ? 'Over' : 'Under') . ')',
                    ]);
                } else {
                    CostEntry::create([
                        'manufacturing_order_id' => $consumption->manufacturing_order_id,
                        'product_id' => $consumption->product_id,
                        'cost_type' => 'material_variance',
                        'quantity' => $varianceQty,
                        'unit_cost' => $product->cost ?? 0,
                        'total_cost' => $costImpact,
                        'notes' => 'Variance: ' . $product->name . ' (' . ($varianceQty > 0 ? 'Over' : 'Under') . ')',
                        'consumption_id' => $consumption->id,
                    ]);
                }
            } elseif ($varianceEntry) {
                $varianceEntry->delete();
            }

        });

        return $this->success($consumption);
    }

    public function destroy(Consumption $consumption)
    {
        $this->authorize('delete', $consumption);

        // Delete associated CostEntry
        CostEntry::where('consumption_id', $consumption->id)->delete();

        $consumption->delete();
        return $this->success(null, [], 204);
    }
}
