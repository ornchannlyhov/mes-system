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
        protected StockService $stockService
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
                    // Note: We don't pass manufacturing_order_id here to avoid StockService creating a duplicate cost entry.
                    // We handle cost entry explicitly below.
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

            return $scrap;
        });
    }
}
