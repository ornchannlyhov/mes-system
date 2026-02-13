<?php

namespace App\Http\Controllers\Api\Execution;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Execution\StoreScrapRequest;
use App\Http\Requests\Execution\UpdateScrapRequest;
use App\Models\Scrap;
use App\Services\ScrapService;
use Illuminate\Http\Request;

class ScrapController extends BaseController
{
    public function __construct(
        protected ScrapService $scrapService
    ) {
    }

    public function index(Request $request)
    {
        $query = Scrap::select(['id', 'product_id', 'manufacturing_order_id', 'work_order_id', 'quantity', 'reason', 'reported_by', 'location_id', 'created_at'])
            ->with(['product:id,name,code,cost,image_url', 'manufacturingOrder:id,name', 'workOrder:id,status', 'reporter:id,name', 'location:id,name,code'])
            ->applyStandardFilters(
                $request,
                [], // Text search handled via relations below
                ['product_id', 'manufacturing_order_id', 'work_order_id'] // Filterable
            );

        if ($request->has('search') && $request->search) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($p) use ($search) {
                    $p->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(code) LIKE ?', ["%{$search}%"]);
                });
            });
        }

        return $this->respondWithPagination(
            $query->paginate($request->get('per_page', 10))
        );
    }

    public function store(StoreScrapRequest $request)
    {
        $this->authorize('create', Scrap::class);

        $validated = $request->validated();
        $validated['reported_by'] = $request->user()->id;

        // DB Transaction for data consistency
        $scrap = $this->scrapService->create($validated);

        return $this->success($scrap->load(['product', 'reporter']), [], 201);
    }

    public function show(Scrap $scrap)
    {
        $this->authorize('view', $scrap);
        return $this->success(
            $scrap->load(['product', 'manufacturingOrder', 'workOrder', 'reporter'])
        );
    }

    // Update and Destroy might be restricted in real-life (accounting impact), but adding stubs for API resource compatibility
    public function update(UpdateScrapRequest $request, Scrap $scrap)
    {
        $this->authorize('update', $scrap);

        $validated = $request->validated();

        $this->scrapService->update($scrap, $validated);

        return $this->success($scrap);
    }

    public function destroy(Scrap $scrap)
    {
        $this->authorize('delete', $scrap);

        $this->scrapService->delete($scrap);

        return $this->success(null, [], 204);
    }
}
