<?php

namespace App\Http\Controllers\Api\Traceability;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Traceability\StoreLotRequest;
use App\Http\Requests\Traceability\UpdateLotRequest;
use App\Models\Lot;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LotController extends BaseController
{
    public function __construct(protected StockService $stockService) {}

    public function index(Request $request)
    {
        $query = Lot::select(['id', 'name', 'notes', 'product_id', 'expiry_date', 'initial_qty', 'created_at'])
            ->with(['product:id,name,code'])
            ->applyStandardFilters(
                $request,
                ['name', 'notes'], 
                ['product_id']
            );

        return $this->respondWithPagination(
            $query->paginate($request->get('per_page', 10)),
            ['counts' => ['total' => Lot::count()]]
        );
    }

    public function store(StoreLotRequest $request)
    {
        $validated = $request->validated();

        $lot = Lot::create(\Illuminate\Support\Arr::except($validated, ['location_id']));

        if (!empty($validated['initial_qty']) && $validated['initial_qty'] > 0 && !empty($validated['location_id'])) {
            $this->stockService->adjustStock([
                'product_id'  => $lot->product_id,
                'location_id' => $validated['location_id'],
                'lot_id'      => $lot->id,
                'quantity'    => $validated['initial_qty'],
                'reason'      => 'initial',
                'notes'       => "Initial stock for lot {$lot->name}",
            ]);
        }

        return $this->success($lot->load('product'), [], 201);
    }

    public function show(Lot $lot)
    {
        return $this->success(
            $lot->load(['product', 'serials', 'stocks.location'])
        );
    }

    public function update(UpdateLotRequest $request, Lot $lot)
    {
        $lot->update($request->validated());

        return $this->success($lot);
    }

    public function destroy(Lot $lot)
    {
        // Block deletion if lot is referenced by any consumption or manufacturing order
        if ($lot->consumptions()->exists()) {
            return $this->error('Cannot delete lot: it has been used in manufacturing consumptions.', 422);
        }

        if ($lot->manufacturingOrders()->exists()) {
            return $this->error('Cannot delete lot: it is linked to manufacturing orders.', 422);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($lot) {
            // Revert all stock that belongs to this lot
            foreach ($lot->stocks as $stock) {
                // Remove the initial adjustment audit record for this lot
                \App\Models\StockAdjustment::where('lot_id', $lot->id)
                    ->where('reason', 'initial')
                    ->delete();

                $stock->delete();
            }

            $lot->delete();
        });

        return $this->success(null, [], 204);
    }

    public function genealogy(Lot $lot)
    {
        return $this->success($this->buildNode($lot));
    }

    private function buildNode($serialOrLot, $depth = 0)
    {
        if ($depth > 5)
            return null; // Prevent deep recursion

        $isSerial = $serialOrLot instanceof \App\Models\Serial;
        $node = [
            'id' => $serialOrLot->id,
            'type' => $isSerial ? 'serial' : 'lot',
            'identifier' => $serialOrLot->name,
            'product' => $serialOrLot->product->name ?? 'Unknown',
            'status' => $serialOrLot->status ?? 'unknown', 
            'children' => [],
        ];

        // Find the MO that produced this item
        $mo = null;
        if ($isSerial) {
            $mo = $serialOrLot->manufacturingOrder;
        } else {
            $mo = \App\Models\ManufacturingOrder::where('lot_id', $serialOrLot->id)->first();
        }

        if ($mo) {
            $mo->load('consumptions.lot.product');
            foreach ($mo->consumptions as $consumption) {
                if ($consumption->lot) {
                    $childNode = $this->buildNode($consumption->lot, $depth + 1);
                    if ($childNode) {
                        $node['children'][] = $childNode;
                    }
                }
            }
        }

        return $node;
    }
}
