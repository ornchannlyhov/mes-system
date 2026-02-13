<?php

namespace App\Services;

use App\Models\ManufacturingOrder;
use App\Models\Consumption;
use App\Models\WorkOrder;
use App\Models\Scrap;
use Illuminate\Support\Facades\DB;

class ManufacturingOrderService
{
    public function __construct(
        protected \App\Services\StockService $stockService
    ) {
    }

    /**
     * Create a manufacturing order with consumptions
     */
    public function create(array $data): ManufacturingOrder
    {
        return DB::transaction(function () use ($data) {
            $data['name'] = ManufacturingOrder::generateName();
            $data['status'] = 'draft';

            if (auth()->check() && !isset($data['organization_id'])) {
                $data['organization_id'] = auth()->user()->organization_id;
            }

            $mo = ManufacturingOrder::create($data);

            // Create consumptions from BOM lines
            $bom = $mo->bom->load('lines');
            foreach ($bom->lines as $line) {
                Consumption::create([
                    'manufacturing_order_id' => $mo->id,
                    'product_id' => $line->product_id,
                    'qty_planned' => $line->quantity * $mo->qty_to_produce / $bom->qty_produced,
                    'qty_consumed' => 0,
                ]);
            }

            return $mo->load(['product', 'bom', 'consumptions.product']);
        });
    }

    /**
     * Prepare MO for execution (Create work orders and reserve stock)
     */
    public function prepareForExecution(ManufacturingOrder $mo): void
    {
        $operations = $mo->bom->operations()->orderBy('sequence')->get();

        foreach ($operations as $operation) {
            $mo->workOrders()->create([
                'operation_id' => $operation->id,
                'work_center_id' => $operation->work_center_id,
                'sequence' => $operation->sequence,
                'status' => 'pending',
                'duration_expected' => $operation->duration_minutes * $mo->qty_to_produce,
            ]);
        }

        // Reserve Stock for Components
        $mo->load('consumptions.product');
        foreach ($mo->consumptions as $consumption) {
            // Find best stock for this component
            $stock = $this->findBestStock($consumption->product_id, $consumption->qty_planned);

            if ($stock) {
                // Update consumption with location/lot
                $consumption->update([
                    'location_id' => $stock->location_id,
                    'lot_id' => $stock->lot_id
                ]);

                $this->stockService->reserve(
                    $consumption->product_id,
                    $stock->location_id,
                    $consumption->qty_planned,
                    $stock->lot_id
                );
            } else {
                // Enforce stock reservation requirement for production components.
                $productName = $consumption->product->name ?? 'Product #' . $consumption->product_id;
                throw new \RuntimeException("Insufficient stock to reserve for component: {$productName} (Required: {$consumption->qty_planned})");
            }
        }
    }

    /**
     * Confirm MO and prepare for execution
     */
    public function confirm(ManufacturingOrder $mo): ManufacturingOrder
    {
        if ($mo->status !== 'draft') {
            throw new \InvalidArgumentException('Only draft orders can be confirmed');
        }

        return DB::transaction(function () use ($mo) {
            $this->prepareForExecution($mo);

            $mo->update(['status' => 'confirmed']);

            return $mo->load('workOrders');
        });
    }

    /**
     * Schedule a manufacturing order
     */
    public function schedule(ManufacturingOrder $mo, array $data): ManufacturingOrder
    {
        // Only allow scheduling for draft, scheduled, and confirmed orders
        if (!in_array($mo->status, ['draft', 'scheduled', 'confirmed'])) {
            throw new \InvalidArgumentException('Cannot schedule orders that are in progress or completed');
        }

        return DB::transaction(function () use ($mo, $data) {
            // If starting from draft, prepare for execution (reserve stock, etc.)
            if ($mo->status === 'draft') {
                $this->prepareForExecution($mo);
            }

            $mo->update([
                'scheduled_start' => $data['scheduled_start'],
                'scheduled_end' => $data['scheduled_end'],
                'status' => 'scheduled',
            ]);

            return $mo->load(['product', 'bom', 'workOrders']);
        });
    }

    /**
     * Find best stock record for a product (simplistic strategy: largest stock first)
     */
    protected function findBestStock(int $productId, float $qtyNeeded): ?\App\Models\Stock
    {
        // 1. Try to find stock with enough quantity (Lot or No Lot)
        $stock = \App\Models\Stock::where('product_id', $productId)
            ->where('quantity', '>=', $qtyNeeded)
            ->orderBy('quantity', 'desc')
            ->first();

        if ($stock) {
            return $stock;
        }

        // 2. Try to find any stock > 0
        $stock = \App\Models\Stock::where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->orderBy('quantity', 'desc')
            ->first();

        if ($stock) {
            return $stock;
        }

        // 3. Fallback: No stock found (can't reserve what we don't have)
        return null;
    }

    /**
     * Start manufacturing order
     */
    public function start(ManufacturingOrder $mo): ManufacturingOrder
    {
        if (!in_array($mo->status, ['confirmed', 'scheduled'])) {
            throw new \InvalidArgumentException('Only confirmed or scheduled orders can be started');
        }

        return DB::transaction(function () use ($mo) {
            $mo->update([
                'status' => 'in_progress',
                'actual_start' => now(),
            ]);

            /** @var WorkOrder|null $firstWo */
            $firstWo = $mo->workOrders()->orderBy('sequence')->first();
            if ($firstWo) {
                $firstWo->update(['status' => 'ready']);
            }

            return $mo;
        });
    }

    /**
     * Complete manufacturing order
     */
    public function complete(ManufacturingOrder $mo, array $data): ManufacturingOrder
    {
        return DB::transaction(function () use ($mo, $data) {
            // Use provided qty or default to qty_to_produce
            $qtyProduced = $data['qty_produced'] ?? $mo->qty_to_produce;
            $locationId = $data['location_id'] ?? null;

            $mo->update([
                'status' => 'done',
                'qty_produced' => $qtyProduced,
                'actual_end' => now(),
            ]);

            // Release Reservations and Consume Stock
            $mo->load('consumptions');

            // Map provided consumptions for easy lookup
            $actualConsumptions = collect($data['consumptions'] ?? [])->keyBy('id');

            foreach ($mo->consumptions as $consumption) {
                // Determine actual quantity consumed
                $actualQty = $consumption->qty_planned;
                if (isset($actualConsumptions[$consumption->id])) {
                    $actualQty = $actualConsumptions[$consumption->id]['qty_consumed'];
                }

                // Calculate Scrapped Quantity for this component
                $scrappedQty = Scrap::where('manufacturing_order_id', $mo->id)
                    ->where('product_id', $consumption->product_id)
                    ->sum('quantity');

                // Use the location we reserved from
                $sourceLocationId = $consumption->location_id;

                if ($sourceLocationId) {
                    $location = \App\Models\Location::find($sourceLocationId);
                    if ($location) {
                        // Release the reservation (of the PLANNED amount)
                        $this->stockService->release(
                            $consumption->product_id,
                            $location->id,
                            $consumption->qty_planned,
                            $consumption->lot_id
                        );

                        // Deduct ONLY what hasn't been deducted via Scrap
                        // Actual Consumed = Total Used. Scrap = Wasted.
                        // If Scrapped, Stock was already deducted in ScrapController.
                        // So we deduct (Actual - Scrapped).
                        $qtyToDeduct = max(0, $actualQty - $scrappedQty);

                        // Actually consume the stock (Subtract NET amount)
                        if ($qtyToDeduct > 0) {
                            $this->stockService->adjust($location, [
                                'product_id' => $consumption->product_id,
                                'quantity' => $qtyToDeduct,
                                'type' => 'subtract',
                                'lot_id' => $consumption->lot_id,
                                'reason' => 'manufacturing_consumption',
                                'reference' => $mo->name,
                                'notes' => 'Consumed for MO #' . $mo->id . ($scrappedQty > 0 ? " (Net of $scrappedQty scrap)" : '')
                            ]);
                        }
                    }
                }

                // Calculate costs BEFORE updating consumption
                $product = $consumption->product;
                if (!$product)
                    $product = \App\Models\Product::find($consumption->product_id);

                $costPerUnit = $product->cost ?? 0;

                // Calculate Variance: Actual - Planned - Scrapped
                // Scrapped items have their own CostEntry (type=scrap).
                // Planned items have their own CostEntry (type=material).
                // Variance catches the unexplained difference.
                $varianceQty = $actualQty - $consumption->qty_planned - $scrappedQty;
                $varianceCost = $varianceQty * $costPerUnit;

                // Update consumption with actual qty and cost impact
                $updateData = [
                    'qty_consumed' => $actualQty,
                    'cost_impact' => $varianceCost,
                ];

                if (isset($actualConsumptions[$consumption->id]['lot_id'])) {
                    $updateData['lot_id'] = $actualConsumptions[$consumption->id]['lot_id'];
                }

                $consumption->update($updateData);

                // Standard Material Cost (Based on PLANNED qty)
                $standardCost = $consumption->qty_planned * $costPerUnit;
                if ($standardCost > 0) {
                    \App\Models\CostEntry::create([
                        'manufacturing_order_id' => $mo->id,
                        'product_id' => $consumption->product_id,
                        'cost_type' => 'material',
                        'quantity' => $consumption->qty_planned,
                        'unit_cost' => $costPerUnit,
                        'total_cost' => $standardCost,
                        'notes' => 'Standard Material: ' . $product->name,
                        'created_at' => now(),
                    ]);
                }

                // Variance Cost (material_variance) - only record if significant
                if (abs($varianceCost) > 0.0001) {
                    \App\Models\CostEntry::create([
                        'manufacturing_order_id' => $mo->id,
                        'product_id' => $consumption->product_id,
                        'cost_type' => 'material_variance',
                        'quantity' => $varianceQty,
                        'unit_cost' => $costPerUnit,
                        'total_cost' => $varianceCost,
                        'notes' => 'Variance: ' . $product->name . ' (' . ($varianceQty > 0 ? 'Over' : 'Under') . ' consumption)',
                        'created_at' => now(),
                    ]);
                }
            }

            // Add finished goods to stock only if location provided
            if ($locationId) {
                $location = \App\Models\Location::findOrFail($locationId);
                $this->stockService->adjust($location, [
                    'product_id' => $mo->product_id,
                    'quantity' => $qtyProduced,
                    'type' => 'add',
                    'lot_id' => $data['lot_id'] ?? null,
                    'reason' => 'manufacturing_production',
                    'reference' => $mo->name,
                    'notes' => 'Production Completed: ' . $mo->name,
                    'manufacturing_order_id' => $mo->id,
                ]);
            }

            return $mo;
        });
    }
    /**
     * Reset manufacturing order to draft
     */
    public function resetToDraft(ManufacturingOrder $mo): ManufacturingOrder
    {
        if (!in_array($mo->status, ['confirmed', 'scheduled'])) {
            throw new \InvalidArgumentException('Only confirmed or scheduled orders can be reset to draft');
        }

        return DB::transaction(function () use ($mo) {
            if (in_array($mo->status, ['confirmed', 'scheduled'])) {
                // Delete Work Orders
                $mo->workOrders()->delete();

                // Release Stock Reservations and Reset Consumptions
                foreach ($mo->consumptions as $consumption) {
                    if ($consumption->location_id) {
                        $this->stockService->release(
                            $consumption->product_id,
                            $consumption->location_id,
                            $consumption->qty_planned,
                            $consumption->lot_id
                        );
                    }
                    $consumption->update(['location_id' => null, 'qty_consumed' => 0]);
                }
            }


            $mo->status = 'draft';
            $mo->save();

            return $mo->load(['product', 'bom', 'consumptions']);
        });
    }
}
