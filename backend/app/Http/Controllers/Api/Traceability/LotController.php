<?php

namespace App\Http\Controllers\Api\Traceability;

use App\Http\Controllers\Controller;
use App\Http\Requests\Traceability\StoreLotRequest;
use App\Http\Requests\Traceability\UpdateLotRequest;
use App\Models\Lot;
use Illuminate\Http\Request;

class LotController extends Controller
{
    public function index(Request $request)
    {
        $query = Lot::with('product');

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        return response()->json(
            $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 10))
        );
    }

    public function store(StoreLotRequest $request)
    {
        $lot = Lot::create($request->validated());

        return response()->json($lot->load('product'), 201);
    }

    public function show(Lot $lot)
    {
        return response()->json(
            $lot->load(['product', 'serials', 'stocks.location'])
        );
    }

    public function update(UpdateLotRequest $request, Lot $lot)
    {
        $validated = $request->validated();

        $lot->update($validated);

        return response()->json($lot);
    }

    public function destroy(Lot $lot)
    {
        $lot->delete();

        return response()->json(null, 204);
    }
}
