<?php

namespace App\Http\Controllers\Api\Execution;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use App\Http\Requests\Execution\UpdateUnbuildOrderRequest;

use App\Models\UnbuildOrder;

use App\Http\Requests\Execution\StoreUnbuildOrderRequest;


class UnbuildOrderController extends BaseController
{
    public function __construct(
        protected \App\Services\UnbuildOrderService $unbuildOrderService
    ) {
    }

    public function index(Request $request)
    {
        $query = UnbuildOrder::select(['id', 'name', 'status', 'product_id', 'bom_id', 'manufacturing_order_id', 'quantity', 'reason', 'created_at'])
            ->with(['product:id,name,code', 'bom:id'])
            ->applyStandardFilters(
                $request,
                ['name', 'reason'], // Searchable
                ['status', 'product_id', 'manufacturing_order_id'] // Filterable
            );

        $counts = $this->getStatusCounts(UnbuildOrder::query(), 'status');

        return $this->respondWithPagination(
            $query->paginate($request->get('per_page', 10)),
            ['counts' => $counts]
        );
    }

    public function store(StoreUnbuildOrderRequest $request)
    {
        $this->authorize('create', UnbuildOrder::class);

        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;
        $validated['name'] = 'UO-' . date('YmdHis'); // Simple generation

        $unbuildOrder = $this->unbuildOrderService->create($validated);

        return $this->success($unbuildOrder, [], 201);
    }

    public function show(UnbuildOrder $unbuildOrder)
    {
        $this->authorize('view', $unbuildOrder);
        return $this->success(
            $unbuildOrder->load(['product', 'bom'])
        );
    }

    public function update(UpdateUnbuildOrderRequest $request, UnbuildOrder $unbuildOrder)
    {
        $this->authorize('update', $unbuildOrder);

        if ($unbuildOrder->status === 'done') {
            return $this->error('Cannot edit completed unbuild order', 422);
        }

        $validated = $request->validated();

        $unbuildOrder->update($validated);
        return $this->success($unbuildOrder);
    }

    public function destroy(UnbuildOrder $unbuildOrder)
    {
        $this->authorize('delete', $unbuildOrder);

        $this->unbuildOrderService->delete($unbuildOrder);
        return $this->success(null, [], 204);
    }
}
