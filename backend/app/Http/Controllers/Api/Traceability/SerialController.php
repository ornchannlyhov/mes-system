<?php

namespace App\Http\Controllers\Api\Traceability;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Traceability\StoreSerialRequest;
use App\Http\Requests\Traceability\UpdateSerialRequest;
use App\Models\Serial;

class SerialController extends Controller
{
    public function index(Request $request)
    {
        $query = Serial::with(['product', 'lot', 'manufacturingOrder']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('lot_id')) {
            $query->where('lot_id', $request->lot_id);
        }
        if ($request->has('search')) {
            $query->where('name', 'ilike', "%{$request->search}%");
        }

        return response()->json(
            $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 10))
        );
    }

    public function store(StoreSerialRequest $request)
    {
        $serial = Serial::create($request->validated());

        return response()->json($serial->load(['product', 'lot']), 201);
    }

    public function show(Serial $serial)
    {
        return response()->json(
            $serial->load(['product', 'lot', 'manufacturingOrder'])
        );
    }

    public function update(UpdateSerialRequest $request, Serial $serial)
    {
        $serial->update($request->validated());

        return response()->json($serial);
    }

    public function destroy(Serial $serial)
    {
        $serial->delete();

        return response()->json(null, 204);
    }

    public function scrap(Serial $serial)
    {
        $serial->update(['status' => 'scrapped']);

        return response()->json($serial);
    }

    public function sell(Serial $serial)
    {
        $serial->update(['status' => 'sold']);

        return response()->json($serial);
    }

    public function genealogy(Serial $serial)
    {
        return response()->json($this->buildNode($serial));
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
