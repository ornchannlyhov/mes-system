<?php

namespace App\Http\Controllers\Api\Engineering;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Engineering\StoreBomRequest;
use App\Models\Bom;
use Illuminate\Http\Request;
use App\Http\Requests\Engineering\UpdateBomRequest;
use App\Http\Requests\Engineering\StoreBomLineRequest;
use App\Http\Requests\Engineering\UpdateBomLineRequest;
use App\Http\Requests\Engineering\StoreBomOperationRequest;
use App\Http\Requests\Engineering\UpdateBomOperationRequest;

class BomController extends BaseController
{
    public function __construct(
        protected \App\Services\BomService $service
    ) {
    }

    public function index(Request $request)
    {
        $query = Bom::select(['id', 'product_id', 'type', 'qty_produced', 'is_active', 'created_at', 'updated_at'])
            ->with([
                'product:id,name,code,image_url',
                'lines:id,bom_id,product_id,quantity,sequence',
                'lines.product:id,name,code',
                'operations:id,bom_id,name,work_center_id,duration_minutes,sequence',
                'operations.workCenter:id,name,code'
            ])
            ->applyStandardFilters(
                $request,
                [], // No searching by text on base BOM table yet, maybe product name?
                ['product_id', 'is_active', 'type'] // Exact filters
            );

        // For BOMs, we might want to search by Product Name.
        // This handles standard filters. If we need relationship search, we add it here manually.
        if ($request->has('search') && $request->search) {
            $search = strtolower($request->search);
            $query->orWhereHas('product', function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(code) LIKE ?', ["%{$search}%"]);
            });
        }

        $counts = $this->getStatusCounts(Bom::query(), 'type');

        return $this->respondWithPagination(
            $query->paginate($request->get('per_page', 10)),
            ['counts' => $counts]
        );
    }

    public function store(StoreBomRequest $request)
    {
        $bom = $this->service->create($request->validated());

        return $this->success($bom, [], 201);
    }

    public function show(Bom $bom)
    {
        return $this->success(
            $bom->load(['product', 'lines.product', 'operations.workCenter'])
        );
    }

    public function update(UpdateBomRequest $request, Bom $bom)
    {
        $bom = $this->service->update($bom, $request->validated());

        return $this->success($bom);
    }

    public function destroy(Bom $bom)
    {
        $bom->delete();

        return $this->success(null, [], 204);
    }

    // ===== BOM Lines (Components) =====
    public function storeLine(StoreBomLineRequest $request, Bom $bom)
    {
        $validated = $request->validated();

        $line = $bom->lines()->create([
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'sequence' => $validated['sequence'] ?? $bom->lines()->count(),
        ]);

        return $this->success($line->load('product'), [], 201);
    }

    public function updateLine(UpdateBomLineRequest $request, Bom $bom, $lineId)
    {
        $line = $bom->lines()->findOrFail($lineId);

        $validated = $request->validated();

        $line->update($validated);

        return $this->success($line->load('product'));
    }

    public function destroyLine(Bom $bom, $lineId)
    {
        $line = $bom->lines()->findOrFail($lineId);
        $line->delete();

        return $this->success(null, [], 204);
    }

    // ===== BOM Operations (Routing) =====
    public function storeOperation(StoreBomOperationRequest $request, Bom $bom)
    {
        $validated = $request->validated();

        $operation = $bom->operations()->create([
            'name' => $validated['name'],
            'work_center_id' => $validated['work_center_id'],
            'duration_minutes' => $validated['duration_minutes'],
            'sequence' => $validated['sequence'] ?? $bom->operations()->count(),
            'needs_quality_check' => $validated['needs_quality_check'] ?? false,
            'instruction_file_url' => $validated['instruction_file_url'] ?? null,
        ]);

        return $this->success($operation->load('workCenter'), [], 201);
    }

    public function updateOperation(UpdateBomOperationRequest $request, Bom $bom, $operationId)
    {
        $operation = $bom->operations()->findOrFail($operationId);

        $validated = $request->validated();

        $operation->update($validated);

        return $this->success($operation->load('workCenter'));
    }

    public function destroyOperation(Bom $bom, $operationId)
    {
        $operation = $bom->operations()->findOrFail($operationId);
        $operation->delete();

        return $this->success(null, [], 204);
    }
}
