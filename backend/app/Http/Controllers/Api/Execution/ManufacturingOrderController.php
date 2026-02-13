<?php

namespace App\Http\Controllers\Api\Execution;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Execution\StoreManufacturingOrderRequest;
use App\Models\ManufacturingOrder;
use App\Services\ManufacturingOrderService;
use Illuminate\Http\Request;

use App\Http\Requests\Execution\UpdateManufacturingOrderRequest;
use App\Http\Requests\Execution\CompleteManufacturingOrderRequest;
use App\Http\Requests\Execution\GetCalendarRequest;
use App\Http\Requests\Execution\RescheduleManufacturingOrderRequest;

class ManufacturingOrderController extends BaseController
{
    public function __construct(
        protected ManufacturingOrderService $service,
        protected \App\Services\SchedulerService $schedulerService
    ) {
    }

    public function index(Request $request)
    {
        $query = ManufacturingOrder::select([
            'id',
            'name',
            'status',
            'priority',
            'qty_to_produce',
            'qty_produced',
            'product_id',
            'bom_id',
            'scheduled_start',
            'scheduled_end',
            'actual_start',
            'actual_end',
            'created_at'
        ])
            ->with([
                'product:id,name,code,uom,image_url',
                'bom:id,type,qty_produced'
            ])
            ->applyStandardFilters(
                $request,
                [], // Text search handled via custom logic below
                ['status', 'product_id', 'priority', 'bom_id'] // Filterable
            );

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($p) use ($search) {
                        $p->where('name', 'ilike', "%{$search}%")
                            ->orWhere('code', 'ilike', "%{$search}%");
                    });
            });
        }

        if ($request->has('start_date')) {
            $query->where('scheduled_start', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('scheduled_start', '<=', $request->end_date);
        }

        $counts = $this->getStatusCounts(ManufacturingOrder::query(), 'status');

        return $this->respondWithPagination(
            $query->paginate($request->get('per_page', 10)),
            ['counts' => $counts]
        );
    }

    public function store(StoreManufacturingOrderRequest $request)
    {
        $mo = $this->service->create($request->validated());

        return $this->success($mo, [], 201);
    }

    public function show(ManufacturingOrder $manufacturingOrder)
    {
        return $this->success(
            $manufacturingOrder->load([
                'product',
                'bom.lines.product',
                'workOrders.workCenter',
                'workOrders.operation',
                'workOrders.assignedUser',
                'consumptions.product',
                'consumptions.lot',
                'scraps',
                'lot',
            ])
        );
    }

    public function update(UpdateManufacturingOrderRequest $request, ManufacturingOrder $manufacturingOrder)
    {
        $manufacturingOrder->update($request->validated());

        return $this->success($manufacturingOrder);
    }

    public function destroy(ManufacturingOrder $manufacturingOrder)
    {
        if ($manufacturingOrder->status !== 'draft') {
            return $this->error('Only draft orders can be deleted', 422);
        }

        $manufacturingOrder->delete();

        return $this->success(null, [], 204);
    }

    public function confirm(ManufacturingOrder $manufacturingOrder)
    {
        try {
            $mo = $this->service->confirm($manufacturingOrder);
            return $this->success($mo);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function start(ManufacturingOrder $manufacturingOrder)
    {
        try {
            $mo = $this->service->start($manufacturingOrder);
            return $this->success($mo);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function complete(CompleteManufacturingOrderRequest $request, ManufacturingOrder $manufacturingOrder)
    {
        $mo = $this->service->complete($manufacturingOrder, $request->validated());

        return $this->success($mo);
    }

    /**
     * Get manufacturing orders for calendar view
     */
    public function calendar(GetCalendarRequest $request)
    {
        $validated = $request->validated();
        $cacheKey = "calendar_mos_{$validated['start']}_{$validated['end']}";

        $orders = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($validated) {
            return ManufacturingOrder::with(['product:id,name,code', 'bom:id,code'])
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('scheduled_start', [$validated['start'], $validated['end']])
                        ->orWhereBetween('scheduled_end', [$validated['start'], $validated['end']])
                        ->orWhere(function ($q) use ($validated) {
                            $q->where('scheduled_start', '<=', $validated['start'])
                                ->where('scheduled_end', '>=', $validated['end']);
                        });
                })
                ->whereNotNull('scheduled_start')
                ->orderBy('scheduled_start')
                ->get(['id', 'name', 'status', 'scheduled_start', 'scheduled_end', 'product_id', 'bom_id']); // Select only needed columns
        });

        return $this->success($orders);
    }

    /**
     * Reschedule a manufacturing order
     */
    public function reschedule(RescheduleManufacturingOrderRequest $request, ManufacturingOrder $manufacturingOrder)
    {
        try {
            $mo = $this->service->schedule($manufacturingOrder, $request->validated());

            return $this->success($mo, ['message' => 'Order rescheduled successfully']);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Auto-schedule a manufacturing order based on capacity
     */
    public function autoSchedule(ManufacturingOrder $manufacturingOrder)
    {
        try {
            $schedule = $this->schedulerService->autoSchedule($manufacturingOrder);

            $mo = $this->service->schedule($manufacturingOrder, $schedule);

            return $this->success($mo, [
                'message' => 'Order scheduled successfully',
                'schedule_details' => $schedule,
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Get capacity data for work centers
     */
    public function capacity(GetCalendarRequest $request)
    {
        $validated = $request->validated();

        $workCenters = \App\Models\WorkCenter::all();

        $capacities = $workCenters->map(function ($wc) use ($validated) {
            return $this->schedulerService->calculateCapacity(
                $wc,
                \Carbon\Carbon::parse($validated['start']),
                \Carbon\Carbon::parse($validated['end'])
            );
        });

        return $this->success($capacities);
    }
    public function reset(ManufacturingOrder $manufacturingOrder)
    {
        try {
            $mo = $this->service->resetToDraft($manufacturingOrder);
            return $this->success($mo);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
