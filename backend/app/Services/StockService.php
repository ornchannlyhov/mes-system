<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Adjust stock at a location
     */
    public function adjust(Location $location, array $data): Stock
    {
        return DB::transaction(function () use ($location, $data) {
            $stock = Stock::firstOrNew([
                'product_id' => $data['product_id'],
                'location_id' => $location->id,
                'lot_id' => $data['lot_id'] ?? null,
            ]);

            switch ($data['type']) {
                case 'set':
                    // If setting lower, we might consider it consumption/loss? 
                    // Complex to track cost difference without old val context easily.
                    $stock->quantity = $data['quantity'];
                    break;
                case 'add':
                    $stock->quantity += $data['quantity'];
                    break;
                case 'subtract':
                    if ($stock->quantity < $data['quantity']) {
                        throw new \InvalidArgumentException('Insufficient stock');
                    }
                    $stock->quantity -= $data['quantity'];

                    // Record Cost Entry if this is related to a MO or Production
                    // We need context to link it to an MO, which isn't in $data yet typically.
                    // However, we can track value reduction generally or add optional context fields.
                    $this->recordCost($data, 'material');
                    break;
            }

            $stock->save();

            // Create Stock History / Adjustment Record
            if (isset($data['reason'])) {
                \App\Models\StockAdjustment::create([
                    'organization_id' => $stock->organization_id ?? auth()->user()->organization_id ?? 1,
                    'product_id' => $stock->product_id,
                    'location_id' => $stock->location_id,
                    'lot_id' => $stock->lot_id,
                    'quantity' => ($data['type'] === 'subtract' ? -1 : 1) * $data['quantity'],
                    'reason' => $data['reason'],
                    'reference' => $data['reference'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'user_id' => $data['user_id'] ?? auth()->id() ?? null,
                ]);
            }

            return $stock->load(['product', 'lot']);
        });
    }

    private function recordCost(array $data, string $type)
    {
        // Only record cost if we have manufacturing context in data
        if (isset($data['manufacturing_order_id'])) {
            $product = \App\Models\Product::find($data['product_id']);
            $cost = $product->cost ?? 0;

            \App\Models\CostEntry::create([
                'manufacturing_order_id' => $data['manufacturing_order_id'],
                'product_id' => $data['product_id'],
                'cost_type' => $type,
                'quantity' => $data['quantity'],
                'unit_cost' => $cost,
                'total_cost' => $cost * $data['quantity'],
                'notes' => 'Stock adjustment'
            ]);
        }
    }

    /**
     * Transfer stock between locations
     */
    public function transfer(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $fromLocation = Location::findOrFail($data['from_location_id']);
            $toLocation = Location::findOrFail($data['to_location_id']);

            // Subtract from source
            $sourceStock = $this->adjust($fromLocation, [
                'product_id' => $data['product_id'],
                'lot_id' => $data['lot_id'] ?? null,
                'quantity' => $data['quantity'],
                'type' => 'subtract',
            ]);

            // Add to destination
            $destStock = $this->adjust($toLocation, [
                'product_id' => $data['product_id'],
                'lot_id' => $data['lot_id'] ?? null,
                'quantity' => $data['quantity'],
                'type' => 'add',
            ]);

            return [
                'from' => $sourceStock,
                'to' => $destStock,
            ];
        });
    }

    /**
     * Reserve stock for a manufacturing order
     */
    public function reserve(int $productId, int $locationId, float $quantity, ?int $lotId = null): Stock
    {
        return DB::transaction(function () use ($productId, $locationId, $quantity, $lotId) {
            $stock = Stock::where([
                'product_id' => $productId,
                'location_id' => $locationId,
                'lot_id' => $lotId,
            ])->lockForUpdate()->first();

            if (!$stock || ($stock->quantity - $stock->reserved_qty) < $quantity) {
                throw new \InvalidArgumentException('Insufficient available stock');
            }

            $stock->reserved_qty += $quantity;
            $stock->save();

            return $stock;
        });
    }

    /**
     * Release reserved stock
     */
    public function release(int $productId, int $locationId, float $quantity, ?int $lotId = null): ?Stock
    {
        return DB::transaction(function () use ($productId, $locationId, $quantity, $lotId) {
            $stock = Stock::where([
                'product_id' => $productId,
                'location_id' => $locationId,
                'lot_id' => $lotId,
            ])->lockForUpdate()->first();

            if ($stock) {
                $stock->reserved_qty = max(0, $stock->reserved_qty - $quantity);
                $stock->save();
            }

            return $stock;
        });
    }

    /**
     * Adjust stock via manual adjustment (increase or decrease)
     */
    public function adjustStock(array $data): Stock
    {
        return DB::transaction(function () use ($data) {
            // Find or create stock record
            $stock = Stock::firstOrNew([
                'product_id' => $data['product_id'],
                'location_id' => $data['location_id'],
                'lot_id' => $data['lot_id'] ?? null,
                'organization_id' => auth()->user()->organization_id,
            ]);

            // Apply adjustment
            $stock->quantity = ($stock->quantity ?? 0) + $data['quantity'];

            // Ensure quantity doesn't go negative
            if ($stock->quantity < 0) {
                throw new \InvalidArgumentException('Adjustment would result in negative stock');
            }

            $stock->save();

            // Create adjustment record
            \App\Models\StockAdjustment::create([
                'organization_id' => auth()->user()->organization_id,
                'product_id' => $data['product_id'],
                'location_id' => $data['location_id'],
                'lot_id' => $data['lot_id'] ?? null,
                'quantity' => $data['quantity'],
                'reason' => $data['reason'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);

            return $stock->load(['product', 'lot', 'location']);
        });
    }
}
