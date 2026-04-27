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
                'duration_expected' => ($operation->duration_minutes * $mo->qty_to_produce) / $mo->bom->qty_produced,
            ]);
        }

        // Reserve Stock for Components
        $mo->load('consumptions.product');
        foreach ($mo->consumptions as $consumption) {
            // If already reserved, skip to avoid double reservation
            if ($consumption->location_id) {
                continue;
            }

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
     * Update a manufacturing order and sync related records if quantity changes
     */
    public function update(ManufacturingOrder $mo, array $data): ManufacturingOrder
    {
        return DB::transaction(function () use ($mo, $data) {
            $oldQty = $mo->qty_to_produce;
            $newQty = $data['qty_to_produce'] ?? $oldQty;

            // Update the MO itself
            $mo->update($data);

            if (abs($newQty - $oldQty) > 0.0001) {
                // 1. Update Consumptions
                $mo->load(['consumptions.product', 'bom.lines']);
                $bom = $mo->bom;

                foreach ($mo->consumptions as $consumption) {
                    // Find corresponding BOM line to get the ratio
                    $line = $bom->lines->where('product_id', $consumption->product_id)->first();
                    if ($line) {
                        $oldPlanned = $consumption->qty_planned;
                        $newPlanned = ($line->quantity * $newQty) / $bom->qty_produced;
                        $delta = $newPlanned - $oldPlanned;

                        $consumption->update(['qty_planned' => $newPlanned]);

                        // 2. Adjust Reservations if already prepared/confirmed
                        if (in_array($mo->status, ['confirmed', 'scheduled', 'in_progress']) && $consumption->location_id) {
                            if ($delta > 0) {
                                // Need MORE stock
                                $this->stockService->reserve(
                                    $consumption->product_id,
                                    $consumption->location_id,
                                    $delta,
                                    $consumption->lot_id
                                );
                            } elseif ($delta < 0) {
                                // Need LESS stock (release some)
                                $this->stockService->release(
                                    $consumption->product_id,
                                    $consumption->location_id,
                                    abs($delta),
                                    $consumption->lot_id
                                );
                            }
                        }
                    }
                }

                // 2. Update Work Orders
                $mo->load(['workOrders.operation', 'bom']);
                foreach ($mo->workOrders as $wo) {
                    if ($wo->operation) {
                        $newDuration = ($wo->operation->duration_minutes * $newQty) / $mo->bom->qty_produced;
                        $wo->update(['duration_expected' => $newDuration]);
                    }
                }
            }

            return $mo->load(['consumptions.product', 'workOrders.operation']);
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

            if (!$locationId) {
                // Try to find a sensible default location (e.g. "Finished Goods" or just any internal location)
                $locationId = \App\Models\Location::where('type', 'internal')
                    ->where('name', 'like', '%Finished%')
                    ->value('id') 
                    ?? \App\Models\Location::where('type', 'internal')->value('id');
            }

            $mo->update([
                'status' => 'done',
                'qty_produced' => $qtyProduced,
                'actual_end' => now(),
            ]);

            // Load consumptions with products to ensure cost data is available
            $mo->load(['consumptions.product', 'bom.lines.product']);

            // Mark all non-done work orders as done
            $mo->workOrders()
                ->where('status', '!=', 'done')
                ->update([
                    'status' => 'done',
                    'finished_at' => now(),
                ]);

            // Calculate labor and overhead costs from BOM operations if work orders weren't processed normally
            // (i.e., when completing MO directly without going through WO start/pause/finish flow)
            $this->calculateLaborOverheadCosts($mo, $qtyProduced);

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
                // Find product cost robustly
                $product = $consumption->product;
                if (!$product) {
                    $product = \App\Models\Product::withoutGlobalScopes()->find($consumption->product_id);
                }

                $costPerUnit = $product ? ($product->cost ?? 0) : 0;
                
                if (!$product) {
                    \Illuminate\Support\Facades\Log::warning("Product not found for consumption #{$consumption->id} during MO #{$mo->id} completion.");
                } elseif ($costPerUnit <= 0) {
                    \Illuminate\Support\Facades\Log::info("Product {$product->id} has 0 cost during MO #{$mo->id} completion.");
                }

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
                
                // Record cost entry if there is a plan or cost, to ensure visibility in reports
                if ($consumption->qty_planned > 0) {
                    \App\Models\CostEntry::create([
                        'organization_id' => $mo->organization_id,
                        'manufacturing_order_id' => $mo->id,
                        'product_id' => $consumption->product_id,
                        'cost_type' => 'material',
                        'quantity' => $consumption->qty_planned,
                        'unit_cost' => $costPerUnit,
                        'total_cost' => $standardCost,
                        'notes' => 'Standard Material: ' . ($product->name ?? 'Product #' . $consumption->product_id),
                        'created_at' => now(),
                    ]);
                }

                // Variance Cost (material_variance) - only record if significant
                if (abs($varianceCost) > 0.0001) {
                    \App\Models\CostEntry::create([
                        'organization_id' => $mo->organization_id,
                        'manufacturing_order_id' => $mo->id,
                        'product_id' => $consumption->product_id,
                        'cost_type' => 'material_variance',
                        'quantity' => $varianceQty,
                        'unit_cost' => $costPerUnit,
                        'total_cost' => $varianceCost,
                        'notes' => 'Variance: ' . ($product->name ?? 'Product #' . $consumption->product_id) . ' (' . ($varianceQty > 0 ? 'Over' : 'Under') . ' consumption)',
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
     * Calculate labor and overhead costs from BOM operations
     * Used when completing MO directly without processing WOs through normal flow
     */
    protected function calculateLaborOverheadCosts(ManufacturingOrder $mo, float $qtyProduced): void
    {
        // Load BOM operations with their work centers
        $mo->load(['bom.operations.workCenter']);

        if (!$mo->bom || !$mo->bom->operations) {
            return;
        }

        foreach ($mo->bom->operations as $operation) {
            $workCenter = $operation->workCenter;
            if (!$workCenter) {
                continue;
            }

            // Calculate expected duration for this MO quantity
            // Duration = (operation duration * MO qty) / BOM qty produced
            $bomQty = $mo->bom->qty_produced ?: 1;
            $durationMinutes = ($operation->duration_minutes * $qtyProduced) / $bomQty;
            $hours = $durationMinutes / 60;

            // Calculate Labor Cost
            $costPerHour = $workCenter->cost_per_hour ?? 0;
            $totalLaborCost = $hours * $costPerHour;

            if ($totalLaborCost > 0) {
                \App\Models\CostEntry::create([
                    'organization_id' => $mo->organization_id,
                    'manufacturing_order_id' => $mo->id,
                    'cost_type' => 'labor',
                    'quantity' => $hours,
                    'unit_cost' => $costPerHour,
                    'total_cost' => $totalLaborCost,
                    'notes' => 'Labor: ' . number_format($hours, 2) . ' hrs @ $' . $costPerHour . '/hr (Operation: ' . $operation->name . ')',
                    'created_at' => now(),
                ]);
            }

            // Calculate Overhead Cost
            $overheadRate = $workCenter->overhead_per_hour ?? 0;
            $totalOverheadCost = $hours * $overheadRate;

            if ($totalOverheadCost > 0) {
                \App\Models\CostEntry::create([
                    'organization_id' => $mo->organization_id,
                    'manufacturing_order_id' => $mo->id,
                    'cost_type' => 'overhead',
                    'quantity' => $hours,
                    'unit_cost' => $overheadRate,
                    'total_cost' => $totalOverheadCost,
                    'notes' => 'Overhead: ' . number_format($hours, 2) . ' hrs @ $' . $overheadRate . '/hr (Operation: ' . $operation->name . ')',
                    'created_at' => now(),
                ]);
            }
        }
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

    /**
     * Delete/Cancel a manufacturing order with full stock reversal
     */
    public function delete(ManufacturingOrder $mo): void
    {
        DB::transaction(function () use ($mo) {
            $mo->load(['consumptions', 'workOrders', 'scraps']);

            // --- Handle stock based on MO status ---

            if (in_array($mo->status, ['confirmed', 'scheduled', 'in_progress'])) {
                // Release stock reservations made during prepareForExecution()
                foreach ($mo->consumptions as $consumption) {
                    if ($consumption->location_id) {
                        $this->stockService->release(
                            $consumption->product_id,
                            $consumption->location_id,
                            $consumption->qty_planned,
                            $consumption->lot_id
                        );

                        // Record the adjustment
                        \App\Models\StockAdjustment::create([
                            'organization_id' => $mo->organization_id,
                            'product_id' => $consumption->product_id,
                            'location_id' => $consumption->location_id,
                            'lot_id' => $consumption->lot_id,
                            'quantity' => 0, // Reservation release, no qty change
                            'reason' => 'mo_cancelled',
                            'reference' => $mo->name,
                            'notes' => 'Stock reservation released: MO cancelled',
                            'user_id' => auth()->id(),
                        ]);
                    }
                }
            }

            if ($mo->status === 'done') {
                // Reverse consumed stock — add materials back
                foreach ($mo->consumptions as $consumption) {
                    if ($consumption->location_id && $consumption->qty_consumed > 0) {
                        $location = \App\Models\Location::find($consumption->location_id);
                        if ($location) {
                            // Calculate net consumed (excluding what was already scrapped)
                            $scrappedQty = Scrap::where('manufacturing_order_id', $mo->id)
                                ->where('product_id', $consumption->product_id)
                                ->sum('quantity');
                            $netConsumed = max(0, $consumption->qty_consumed - $scrappedQty);

                            if ($netConsumed > 0) {
                                $this->stockService->adjust($location, [
                                    'product_id' => $consumption->product_id,
                                    'quantity' => $netConsumed,
                                    'type' => 'add',
                                    'lot_id' => $consumption->lot_id,
                                    'reason' => 'mo_cancelled',
                                    'reference' => $mo->name,
                                    'notes' => 'Materials returned: MO cancelled/deleted',
                                ]);
                            }
                        }
                    }
                }

                // Reverse scrapped stock — add scrapped materials back
                foreach ($mo->scraps as $scrap) {
                    if ($scrap->location_id && $scrap->quantity > 0) {
                        $location = \App\Models\Location::find($scrap->location_id);
                        if ($location) {
                            $this->stockService->adjust($location, [
                                'product_id' => $scrap->product_id,
                                'quantity' => $scrap->quantity,
                                'type' => 'add',
                                'lot_id' => $scrap->lot_id,
                                'reason' => 'mo_cancelled',
                                'reference' => $mo->name,
                                'notes' => 'Scrapped materials returned: MO cancelled/deleted',
                            ]);
                        }
                    }
                }

                // Remove finished goods that were produced
                if ($mo->qty_produced > 0) {
                    // Find the stock adjustment that added the finished goods
                    $productionAdjustment = \App\Models\StockAdjustment::where('reference', $mo->name)
                        ->where('reason', 'manufacturing_production')
                        ->where('product_id', $mo->product_id)
                        ->first();

                    if ($productionAdjustment) {
                        $location = \App\Models\Location::find($productionAdjustment->location_id);
                        if ($location) {
                            try {
                                $this->stockService->adjust($location, [
                                    'product_id' => $mo->product_id,
                                    'quantity' => $mo->qty_produced,
                                    'type' => 'subtract',
                                    'lot_id' => $productionAdjustment->lot_id,
                                    'reason' => 'mo_cancelled',
                                    'reference' => $mo->name,
                                    'notes' => 'Finished goods removed: MO cancelled/deleted',
                                ]);
                            } catch (\InvalidArgumentException $e) {
                                // Stock may have been consumed/transferred already — log but don't block delete
                                \Log::warning("Could not reverse finished goods for MO {$mo->name}: {$e->getMessage()}");
                            }
                        }
                    }
                }
            }

            // --- Cleanup related records ---

            // Delete all cost entries for this MO
            \App\Models\CostEntry::where('manufacturing_order_id', $mo->id)->delete();

            // Delete all scraps for this MO
            $mo->scraps()->delete();

            // Delete all work orders for this MO
            $mo->workOrders()->forceDelete();

            // Delete all consumptions for this MO
            $mo->consumptions()->delete();

            // Soft-delete the MO
            $mo->delete();
        });
    }
}
