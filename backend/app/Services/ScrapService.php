<?php

namespace App\Services;

use App\Models\Scrap;
use App\Models\Location;
use App\Models\CostEntry;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ScrapService
{
    public function __construct(
        protected StockService $stockService,
        protected OeeService $oeeService
    ) {
    }

    public function create(array $data): Scrap
    {
        return DB::transaction(function () use ($data) {
            // 1. Create Scrap Record
            $scrap = Scrap::create($data);

            // 2. Deduct from Inventory (if location is provided)
            if (isset($data['location_id'])) {
                $location = Location::findOrFail($data['location_id']);

                $this->stockService->adjust($location, [
                    'product_id' => $data['product_id'],
                    'lot_id' => $data['lot_id'] ?? null,
                    'quantity' => $data['quantity'],
                    'type' => 'subtract',
                    'reason' => 'scrap_record',
                    'reference' => 'Scrap #' . $scrap->id,
                    'notes' => $data['reason'] ?? 'Material scrapped',
                ]);
            }

            // 3. Record Scrap Cost
            $product = Product::find($data['product_id']);
            $cost = $product->cost ?? 0;

            if ($cost > 0) {
                CostEntry::create([
                    'manufacturing_order_id' => $data['manufacturing_order_id'] ?? null,
                    'product_id' => $data['product_id'],
                    'work_order_id' => $data['work_order_id'] ?? null,
                    'cost_type' => 'scrap',
                    'quantity' => $data['quantity'],
                    'unit_cost' => $cost,
                    'total_cost' => $cost * $data['quantity'],
                    'notes' => 'Scrap: ' . ($data['reason'] ?? 'No reason provided'),
                    'created_at' => now(),
                ]);
            }

            if (isset($data['work_order_id'])) {
                $workOrder = \App\Models\WorkOrder::find($data['work_order_id']);
                if ($workOrder && $workOrder->status === 'done') {
                    $this->oeeService->calculate($workOrder);
                }
            }

            return $scrap;
        });
    }

    public function update(Scrap $scrap, array $data): Scrap
    {
        return DB::transaction(function () use ($scrap, $data) {
            $oldQuantity = $scrap->quantity;
            $newQuantity = $data['quantity'] ?? $oldQuantity;

            // 1. Update Stock if quantity changed
            if ($newQuantity != $oldQuantity && $scrap->location_id) {
                $location = Location::find($scrap->location_id);
                if ($location) {
                    $delta = $newQuantity - $oldQuantity;
                    // If delta > 0 (Increased scrap), we subtract more stock.
                    // If delta < 0 (Decreased scrap), we subtract negative stock (add).
                    // StockService 'subtract' expects positive qty for subtraction.
                    // Let's use 'add'/'subtract' explicitly for clarity.

                    if ($delta > 0) {
                        $this->stockService->adjust($location, [
                            'product_id' => $scrap->product_id,
                            'lot_id' => $scrap->lot_id,
                            'quantity' => $delta,
                            'type' => 'subtract',
                            'reason' => 'scrap_update',
                            'reference' => 'Scrap #' . $scrap->id,
                            'notes' => 'Quantity increased'
                        ]);
                    } else {
                        $this->stockService->adjust($location, [
                            'product_id' => $scrap->product_id,
                            'lot_id' => $scrap->lot_id,
                            'quantity' => abs($delta),
                            'type' => 'add',
                            'reason' => 'scrap_update',
                            'reference' => 'Scrap #' . $scrap->id,
                            'notes' => 'Quantity decreased'
                        ]);
                    }
                }
            }

            // 2. Update Scrap Record
            $scrap->update($data);

            // 3. Update Cost Entry
            $costEntry = CostEntry::where('scrap_id', $scrap->id)->first();
            if ($costEntry) {
                $product = $scrap->product;
                $cost = $product->cost ?? 0;

                $costEntry->update([
                    'quantity' => $newQuantity,
                    'total_cost' => $cost * $newQuantity,
                    'notes' => 'Scrap (Updated): ' . ($data['reason'] ?? $scrap->reason)
                ]);
            }

            // 4. Update OEE if related to WorkOrder (Trigger Recalc)
            if ($scrap->work_order_id) {
                $workOrder = \App\Models\WorkOrder::find($scrap->work_order_id);
                if ($workOrder && $workOrder->status === 'done') {
                    $this->oeeService->calculate($workOrder);
                }
            }

            return $scrap;
        });
    }
    public function delete(Scrap $scrap): void
    {
        DB::transaction(function () use ($scrap) {
            // 1. Revert Stock (Add back)
            if ($scrap->location_id && $scrap->product_id) {
                $location = Location::find($scrap->location_id);
                if ($location) {
                    $this->stockService->adjust($location, [
                        'product_id' => $scrap->product_id,
                        'lot_id' => $scrap->lot_id,
                        'quantity' => $scrap->quantity,
                        'type' => 'add', // Revert subtract
                        'reason' => 'scrap_deletion',
                        'reference' => 'Scrap #' . $scrap->id,
                        'notes' => 'Reverted after deletion'
                    ]);
                }
            }

            // 2. Delete Associated CostEntry
            CostEntry::where('scrap_id', $scrap->id)->delete();

            $workOrderId = $scrap->work_order_id;

            // 3. Delete Scrap
            $scrap->delete();

            // 4. Update OEE if related to WorkOrder
            if ($workOrderId) {
                $workOrder = \App\Models\WorkOrder::find($workOrderId);
                if ($workOrder && $workOrder->status === 'done') {
                    $this->oeeService->calculate($workOrder);
                }
            }
        });
    }
}
