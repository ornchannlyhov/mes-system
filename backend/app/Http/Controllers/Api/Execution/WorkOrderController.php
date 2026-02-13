<?php

namespace App\Http\Controllers\Api\Execution;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Execution\StoreWorkOrderRequest;
use App\Models\WorkOrder;
use Illuminate\Http\Request;

use App\Http\Requests\Execution\UpdateWorkOrderRequest;
use App\Http\Requests\Execution\FinishWorkOrderRequest;
use App\Services\OeeService;

class WorkOrderController extends BaseController
{
    public function __construct(
        protected OeeService $oeeService,
        protected \App\Services\WorkOrderService $service
    ) {
    }

    public function index(Request $request)
    {
        $query = WorkOrder::select([
            'id',
            'status',
            'qa_status',
            'manufacturing_order_id',
            'work_center_id',
            'operation_id',
            'assigned_to',
            'qa_by',
            'qa_at',
            'qa_comments',
            'duration_expected',
            'duration_actual',
            'started_at',
            'finished_at',
            'created_at',
            'updated_at'
        ])
            ->with([
                'manufacturingOrder:id,name,product_id',
                'manufacturingOrder.product:id,name,code',
                'workCenter:id,name,code',
                'assignedUser:id,name',
                'operation:id,name,needs_quality_check,instruction_file_url',
                'qaUser:id,name'
            ])
            ->applyStandardFilters(
                $request,
                [], // Text search handled via relations below
                ['status', 'work_center_id', 'assigned_to', 'manufacturing_order_id', 'qa_status'] // Filterable
            );

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('manufacturingOrder', function ($mo) use ($search) {
                    $mo->where('name', 'ilike', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%");
                })->orWhereHas('manufacturingOrder.product', function ($prod) use ($search) {
                    $prod->where('name', 'ilike', "%{$search}%")
                        ->orWhere('code', 'ilike', "%{$search}%");
                })->orWhereHas('workCenter', function ($wc) use ($search) {
                    $wc->where('name', 'ilike', "%{$search}%");
                })->orWhereHas('operation', function ($op) use ($search) {
                    $op->where('name', 'ilike', "%{$search}%");
                });
            });
        }

        if ($request->has('has_qa') && $request->has_qa === 'true') {
            $query->whereNotNull('qa_status')->where('qa_status', '!=', 'pending');
        }

        $counts = $this->getStatusCounts(WorkOrder::query(), 'status');
        $qaCounts = $this->getStatusCounts(WorkOrder::query(), 'qa_status');

        return $this->respondWithPagination(
            $query->paginate($request->get('per_page', 10)),
            ['counts' => $counts, 'qa_counts' => $qaCounts]
        );
    }

    public function store(StoreWorkOrderRequest $request)
    {
        $workOrder = WorkOrder::create($request->validated());

        return $this->success($workOrder->load(['operation', 'workCenter']), [], 201);
    }

    public function show(WorkOrder $workOrder)
    {
        return $this->success(
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

        return $this->success($workOrder);
    }

    public function destroy(WorkOrder $workOrder)
    {
        $workOrder->delete();

        return $this->success(null, [], 204);
    }

    // Start the work order timer
    public function start(WorkOrder $workOrder)
    {
        try {
            $this->service->start($workOrder);
            return $this->success($workOrder);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    // Pause the work order
    public function pause(WorkOrder $workOrder)
    {
        try {
            $this->service->pause($workOrder);
            return $this->success($workOrder);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    // Resume the work order
    public function resume(WorkOrder $workOrder)
    {
        try {
            $this->service->resume($workOrder);
            return $this->success($workOrder);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    // Finish the work order
    public function finish(FinishWorkOrderRequest $request, WorkOrder $workOrder)
    {
        $validated = $request->validated();
        // Handle explicit null or float casting
        $qtyProduced = isset($validated['quantity_produced']) && $validated['quantity_produced'] !== ''
            ? (float) $validated['quantity_produced']
            : null;

        try {
            $this->service->finish($workOrder, $qtyProduced);
            return $this->success($workOrder);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
