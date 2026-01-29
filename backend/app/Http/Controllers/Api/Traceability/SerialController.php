<?php

namespace App\Http\Controllers\Api\Traceability;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use App\Http\Requests\Traceability\StoreSerialRequest;
use App\Http\Requests\Traceability\UpdateSerialRequest;
use App\Models\Serial;

class SerialController extends BaseController
{
    public function index(Request $request)
    {
        $query = Serial::with(['product', 'lot', 'manufacturingOrder'])
            ->applyStandardFilters(
                $request,
                ['name'], // Searchable
                ['product_id', 'status', 'lot_id'] // Filterable
            );

        $counts = $this->getStatusCounts(Serial::query(), 'status');

        return $this->respondWithPagination(
            $query->paginate($request->get('per_page', 10)),
            ['counts' => $counts]
        );
    }

    public function store(StoreSerialRequest $request)
    {
        $serial = Serial::create($request->validated());

        return $this->success($serial->load(['product', 'lot']), [], 201);
    }

    public function show(Serial $serial)
    {
        return $this->success(
            $serial->load(['product', 'lot', 'manufacturingOrder'])
        );
    }

    public function update(UpdateSerialRequest $request, Serial $serial)
    {
        $serial->update($request->validated());

        return $this->success($serial);
    }

    public function destroy(Serial $serial)
    {
        $serial->delete();

        return $this->success(null, [], 204);
    }

    public function scrap(Serial $serial)
    {
        $serial->update(['status' => 'scrapped']);

        return $this->success($serial);
    }

    public function sell(Serial $serial)
    {
        $serial->update(['status' => 'sold']);

        return $this->success($serial);
    }

    public function genealogy(Serial $serial)
    {
        return $this->success($this->buildNode($serial));
    }

    private function buildNode($serialOrLot, $depth = 0)
    {
        if ($depth > 5)
            return null; // Prevent deep recursion

        $isSerial = $serialOrLot instanceof Serial;
        $node = [
            'id' => $serialOrLot->id,
            'type' => $isSerial ? 'serial' : 'lot',
            'identifier' => $serialOrLot->name,
            'product' => $serialOrLot->product->name ?? 'Unknown',
            'status' => $serialOrLot->status ?? 'unknown',
            'children' => [],
        ];

        // Find the MO that produced this item
        // For Serial: $serial->manufacturingOrder
        // For Lot: Need to find MO where lot_id = this lot
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
