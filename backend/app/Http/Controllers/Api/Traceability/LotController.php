<?php

namespace App\Http\Controllers\Api\Traceability;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Traceability\StoreLotRequest;
use App\Http\Requests\Traceability\UpdateLotRequest;
use App\Models\Lot;
use Illuminate\Http\Request;

class LotController extends BaseController
{
    public function index(Request $request)
    {
        $query = Lot::with('product')
            ->applyStandardFilters(
                $request,
                ['name', 'notes'], // Searchable
                ['product_id'] // Filterable
            );

        return $this->respondWithPagination(
            $query->paginate($request->get('per_page', 10))
        );
    }

    public function store(StoreLotRequest $request)
    {
        $lot = Lot::create($request->validated());

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
        $lot->delete();

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
            'status' => $serialOrLot->status ?? 'unknown', // Lot might not have status, Serial does
            'children' => [],
        ];

        // Find the MO that produced this item
        $mo = null;
        if ($isSerial) {
            // @var \App\Models\Serial $serialOrLot
            $mo = $serialOrLot->manufacturingOrder;
        } else {
            // For Lot, we find MO where this lot was output
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
