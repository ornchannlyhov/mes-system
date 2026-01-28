<?php

namespace App\Http\Controllers\Api\Engineering;

use App\Http\Controllers\Controller;
use App\Http\Requests\Engineering\StoreBomRequest;
use App\Models\Bom;
use Illuminate\Http\Request;
use App\Http\Requests\Engineering\UpdateBomRequest;
use App\Http\Requests\Engineering\StoreBomLineRequest;
use App\Http\Requests\Engineering\UpdateBomLineRequest;
use App\Http\Requests\Engineering\StoreBomOperationRequest;
use App\Http\Requests\Engineering\UpdateBomOperationRequest;

class BomController extends Controller
{
    public function __construct(
        protected \App\Services\BomService $service
    ) {
    }
    public function index(Request $request)
    {
        $query = Bom::with(['product', 'lines.product', 'operations.workCenter']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $boms = $query->orderBy('id', 'desc')->get();

        return response()->json(['data' => $boms]);
    }

    public function store(StoreBomRequest $request)
    {
        $bom = $this->service->create($request->validated());

        return response()->json($bom, 201);
    }

    public function show(Bom $bom)
    {
        return response()->json(
            $bom->load(['product', 'lines.product', 'operations.workCenter'])
        );
    }

    public function update(UpdateBomRequest $request, Bom $bom)
    {
        $bom = $this->service->update($bom, $request->validated());

        return response()->json($bom);
    }

    public function destroy(Bom $bom)
    {
        $bom->delete();

        return response()->json(null, 204);
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

        return response()->json($line->load('product'), 201);
    }

    public function updateLine(UpdateBomLineRequest $request, Bom $bom, $lineId)
    {
        $line = $bom->lines()->findOrFail($lineId);

        $validated = $request->validated();

        $line->update($validated);

        return response()->json($line->load('product'));
    }

    public function destroyLine(Bom $bom, $lineId)
    {
        $line = $bom->lines()->findOrFail($lineId);
        $line->delete();

        return response()->json(null, 204);
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

        return response()->json($operation->load('workCenter'), 201);
    }

    public function updateOperation(UpdateBomOperationRequest $request, Bom $bom, $operationId)
    {
        $operation = $bom->operations()->findOrFail($operationId);

        $validated = $request->validated();

        $operation->update($validated);

        return response()->json($operation->load('workCenter'));
    }

    public function destroyOperation(Bom $bom, $operationId)
    {
        $operation = $bom->operations()->findOrFail($operationId);
        $operation->delete();

        return response()->json(null, 204);
    }
}
