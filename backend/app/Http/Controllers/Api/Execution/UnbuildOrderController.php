<?php

namespace App\Http\Controllers\Api\Execution;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Execution\UpdateUnbuildOrderRequest;

use App\Models\UnbuildOrder;

use App\Http\Requests\Execution\StoreUnbuildOrderRequest;


class UnbuildOrderController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(UnbuildOrder::with(['product', 'bom'])->paginate($request->get('per_page', 10)));
    }

    public function store(StoreUnbuildOrderRequest $request)
    {
        $this->authorize('create', UnbuildOrder::class);

        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;
        $validated['name'] = 'UO-' . date('YmdHis'); // Simple generation

        $unbuildOrder = UnbuildOrder::create($validated);
        return response()->json($unbuildOrder, 201);
    }

    public function show(UnbuildOrder $unbuildOrder)
    {
        $this->authorize('view', $unbuildOrder);
        return response()->json($unbuildOrder->load(['product', 'bom']));
    }

    public function update(UpdateUnbuildOrderRequest $request, UnbuildOrder $unbuildOrder)
    {
        $this->authorize('update', $unbuildOrder);

        if ($unbuildOrder->status === 'done') {
            return response()->json(['message' => 'Cannot edit completed unbuild order'], 422);
        }

        $validated = $request->validated();

        $unbuildOrder->update($validated);
        return response()->json($unbuildOrder);
    }

    public function destroy(UnbuildOrder $unbuildOrder)
    {
        $this->authorize('delete', $unbuildOrder);

        $unbuildOrder->delete();
        return response()->json(null, 204);
    }
}
