<?php

namespace App\Services;

use App\Models\UnbuildOrder;
use App\Models\Bom;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class UnbuildOrderService
{
    public function __construct(
        protected StockService $stockService
    ) {
    }

    /**
     * Create an unbuild order and process stock adjustments.
     */
    public function create(array $data): UnbuildOrder
    {
        return DB::transaction(function () use ($data) {
            // 1. Validate required locations for stock movement
            if (empty($data['location_id'])) {
                throw new \InvalidArgumentException('Source location is required for unbuild stock movement.');
            }

            if (empty($data['component_location_id'])) {
                throw new \InvalidArgumentException('Component return location is required for unbuild stock movement.');
            }

            // 2. Create the Unbuild Order record
            $unbuildOrder = UnbuildOrder::create($data);

            // 3. Deduct Finished Product from Stock
            $location = Location::findOrFail($data['location_id']);
            $this->stockService->adjust($location, [
                'product_id' => $unbuildOrder->product_id,
                'lot_id' => $unbuildOrder->lot_id ?? null,
                'quantity' => $unbuildOrder->quantity,
                'type' => 'subtract',
                'reason' => 'manufacturing_production',
                'reference' => $unbuildOrder->name,
                'notes' => "Deducted finished good for unbuild " . $unbuildOrder->name
            ]);

            // 4. Add Components Back to Stock
            $bom = Bom::with('lines.product')->findOrFail($unbuildOrder->bom_id);
            $destLocation = Location::findOrFail($data['component_location_id']);

            foreach ($bom->lines as $line) {
                // Formula: (Unbuild Qty / BOM Output Qty) * Component Qty
                $componentQty = ($unbuildOrder->quantity / $bom->qty_produced) * $line->quantity;

                $this->stockService->adjust($destLocation, [
                    'product_id' => $line->product_id,
                    'quantity' => $componentQty,
                    'type' => 'add',
                    'reason' => 'manufacturing_consumption',
                    'reference' => $unbuildOrder->name,
                    'notes' => "Returned components from unbuild " . $unbuildOrder->name
                ]);
            }

            return $unbuildOrder;
        });
    }

    /**
     * Delete an unbuild order and revert stock adjustments.
     */
    public function delete(UnbuildOrder $unbuildOrder): void
    {
        DB::transaction(function () use ($unbuildOrder) {
            // 1. Revert Finished Product Deduction (Add it back)
            if ($unbuildOrder->location_id) {
                $location = Location::findOrFail($unbuildOrder->location_id);
                $this->stockService->adjust($location, [
                    'product_id' => $unbuildOrder->product_id,
                    'lot_id' => $unbuildOrder->lot_id ?? null,
                    'quantity' => $unbuildOrder->quantity,
                    'type' => 'add', // Revert subtract
                    'reason' => 'manufacturing_production',
                    'reference' => $unbuildOrder->name,
                    'notes' => "Reverted finished good addition after unbuild deletion"
                ]);
            }

            // 2. Revert Component Addition (Subtract them)
            $bom = Bom::with('lines')->findOrFail($unbuildOrder->bom_id);
            $destLocationId = $unbuildOrder->component_location_id ?? $unbuildOrder->location_id;

            if ($destLocationId) {
                $destLocation = Location::findOrFail($destLocationId);
                foreach ($bom->lines as $line) {
                    $componentQty = ($unbuildOrder->quantity / $bom->qty_produced) * $line->quantity;

                    $this->stockService->adjust($destLocation, [
                        'product_id' => $line->product_id,
                        'quantity' => $componentQty,
                        'type' => 'subtract', // Revert add
                        'reason' => 'manufacturing_consumption',
                        'reference' => $unbuildOrder->name,
                        'notes' => "Removed returned components after unbuild deletion"
                    ]);
                }
            }

            $unbuildOrder::where('id', $unbuildOrder->id)->delete();
        });
    }
}
