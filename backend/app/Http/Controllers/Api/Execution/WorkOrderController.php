<?php

namespace App\Http\Controllers\Api\Execution;

use App\Http\Controllers\Controller;
use App\Http\Requests\Execution\StoreWorkOrderRequest;
use App\Models\WorkOrder;
use Illuminate\Http\Request;

use App\Http\Requests\Execution\UpdateWorkOrderRequest;
use App\Http\Requests\Execution\FinishWorkOrderRequest;
use App\Services\OeeService;

class WorkOrderController extends Controller
{
    public function __construct(
        protected OeeService $oeeService,
        protected \App\Services\WorkOrderService $service
    ) {
    }

    public function index(Request $request)
    {
        $query = WorkOrder::with(['manufacturingOrder.product', 'workCenter', 'assignedUser', 'operation', 'qaUser']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('work_center_id')) {
            $query->where('work_center_id', $request->work_center_id);
        }
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        if ($request->has('manufacturing_order_id')) {
            $query->where('manufacturing_order_id', $request->manufacturing_order_id);
        }

        $orders = $query->orderBy('sequence')->get();

        return response()->json(['data' => $orders]);
    }

    public function store(StoreWorkOrderRequest $request)
    {
        $workOrder = WorkOrder::create($request->validated());

        return response()->json($workOrder->load(['operation', 'workCenter']), 201);
    }

    public function show(WorkOrder $workOrder)
    {
        return response()->json(
            $workOrder->load([
                'manufacturingOrder.product',
                'operation',
                'workCenter',
                'assignedUser',
                'qaUser',
                'scraps',
            ])
        );
    }

    public function update(UpdateWorkOrderRequest $request, WorkOrder $workOrder)
    {
        $data = $request->validated();

        if (isset($data['qa_status']) && $data['qa_status'] !== 'pending') {
            $data['qa_by'] = auth()->id();
            $data['qa_at'] = now();
        }

        $workOrder->update($data);

        return response()->json($workOrder);
    }

    public function destroy(WorkOrder $workOrder)
    {
        $workOrder->delete();

        return response()->json(null, 204);
    }

    // Start the work order timer
    public function start(WorkOrder $workOrder)
    {
        try {
            $this->service->start($workOrder);
            return response()->json($workOrder);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // Pause the work order
    public function pause(WorkOrder $workOrder)
    {
        try {
            $this->service->pause($workOrder);
            return response()->json($workOrder);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // Resume the work order
    public function resume(WorkOrder $workOrder)
    {
        try {
            $this->service->resume($workOrder);
            return response()->json($workOrder);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // Finish the work order
    public function finish(FinishWorkOrderRequest $request, WorkOrder $workOrder)
    {
        $validated = $request->validated();
        $qtyProduced = $validated['quantity_produced'] !== null ? (float) $validated['quantity_produced'] : null;

        try {
            $this->service->finish($workOrder, $qtyProduced);
            return response()->json($workOrder);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
