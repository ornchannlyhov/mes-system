<?php

namespace App\Http\Controllers\Api\Execution;

use App\Http\Controllers\Controller;
use App\Models\Consumption;
use App\Models\Product;
use App\Models\CostEntry;
use Illuminate\Http\Request;
use App\Http\Requests\Execution\StoreConsumptionRequest;
use App\Http\Requests\Execution\UpdateConsumptionRequest;

class ConsumptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Consumption::with(['product', 'manufacturingOrder']);

        if ($request->has('has_variance')) {
            // Filter where variance is not 0 (qty_consumed != qty_planned)
            $query->whereRaw('qty_consumed != qty_planned');
        }

        if ($request->has('manufacturing_order_id')) {
            $query->where('manufacturing_order_id', $request->manufacturing_order_id);
        }

        $consumptions = $query->latest()->paginate(20);

        return response()->json($consumptions);
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
        if (abs($costImpact) > 0.0001) {
            CostEntry::create([
                'manufacturing_order_id' => $validated['manufacturing_order_id'],
                'product_id' => $validated['product_id'],
                'cost_type' => 'material_variance',
                'quantity' => $varianceQty,
                'unit_cost' => $product->cost ?? 0,
                'total_cost' => $costImpact,
                'notes' => 'Manual Variance: ' . $product->name . ' (' . ($varianceQty > 0 ? 'Over' : 'Under') . ' consumption)',
                'consumption_id' => $consumption->id, // Link to source
                'created_at' => now(),
            ]);
        }

        return response()->json($consumption->load('product'), 201);
    }

    public function update(UpdateConsumptionRequest $request, Consumption $consumption)
    {
        $this->authorize('update', $consumption);

        $validated = $request->validated();

        $product = $consumption->product;
        $varianceQty = $validated['qty_consumed'] - $validated['qty_planned'];
        $costImpact = $varianceQty * ($product->cost ?? 0);

        $consumption->update([
            'qty_planned' => $validated['qty_planned'],
            'qty_consumed' => $validated['qty_consumed'],
            'cost_impact' => $costImpact
        ]);

        // Auto-Adjust CostEntry for Material Variance
        $costEntry = CostEntry::where('consumption_id', $consumption->id)
            ->where('cost_type', 'material_variance')
            ->first();

        // If variance exists (non-zero cost impact), update or create the entry
        if (abs($costImpact) > 0.0001) {
            if ($costEntry) {
                // Update existing
                $costEntry->update([
                    'quantity' => $varianceQty,
                    'total_cost' => $costImpact,
                    'notes' => 'Manual Variance (Updated): ' . $product->name . ' (' . ($varianceQty > 0 ? 'Over' : 'Under') . ' consumption)',
                ]);
            } else {
                // Create new if it didn't exist before (e.g. variance was 0 initially)
                CostEntry::create([
                    'manufacturing_order_id' => $consumption->manufacturing_order_id,
                    'product_id' => $consumption->product_id,
                    'cost_type' => 'material_variance',
                    'quantity' => $varianceQty,
                    'unit_cost' => $product->cost ?? 0,
                    'total_cost' => $costImpact,
                    'notes' => 'Manual Variance (Updated): ' . $product->name . ' (' . ($varianceQty > 0 ? 'Over' : 'Under') . ' consumption)',
                    'consumption_id' => $consumption->id,
                ]);
            }
        } elseif ($costEntry) {
            // Variance is now zero, delete the cost entry if it existed
            $costEntry->delete();
        }

        return response()->json($consumption);
    }

    public function destroy(Consumption $consumption)
    {
        $this->authorize('delete', $consumption);

        $consumption->delete();
        return response()->json(null, 204);
    }
}
