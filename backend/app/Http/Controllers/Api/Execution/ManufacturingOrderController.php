<?php

namespace App\Http\Controllers\Api\Execution;

use App\Http\Controllers\Controller;
use App\Http\Requests\Execution\StoreManufacturingOrderRequest;
use App\Models\ManufacturingOrder;
use App\Services\ManufacturingOrderService;
use Illuminate\Http\Request;

use App\Http\Requests\Execution\UpdateManufacturingOrderRequest;
use App\Http\Requests\Execution\CompleteManufacturingOrderRequest;
use App\Http\Requests\Execution\GetCalendarRequest;
use App\Http\Requests\Execution\RescheduleManufacturingOrderRequest;

class ManufacturingOrderController extends Controller
{
    public function __construct(
        protected ManufacturingOrderService $service
    ) {
    }

    public function index(Request $request)
    {
        $query = ManufacturingOrder::with(['product', 'bom']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $orders]);
    }

    public function store(StoreManufacturingOrderRequest $request)
    {
        $mo = $this->service->create($request->validated());

        return response()->json($mo, 201);
    }

    public function show(ManufacturingOrder $manufacturingOrder)
    {
        return response()->json([
            'data' => $manufacturingOrder->load([
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
        ]);
    }

    public function update(UpdateManufacturingOrderRequest $request, ManufacturingOrder $manufacturingOrder)
    {
        $manufacturingOrder->update($request->validated());

        return response()->json($manufacturingOrder);
    }

    public function destroy(ManufacturingOrder $manufacturingOrder)
    {
        if ($manufacturingOrder->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft orders can be deleted'
            ], 422);
        }

        $manufacturingOrder->delete();

        return response()->json(null, 204);
    }

    public function confirm(ManufacturingOrder $manufacturingOrder)
    {
        try {
            $mo = $this->service->confirm($manufacturingOrder);
            return response()->json($mo);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function start(ManufacturingOrder $manufacturingOrder)
    {
        try {
            $mo = $this->service->start($manufacturingOrder);
            return response()->json($mo);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function complete(CompleteManufacturingOrderRequest $request, ManufacturingOrder $manufacturingOrder)
    {
        $mo = $this->service->complete($manufacturingOrder, $request->validated());

        return response()->json($mo);
    }

    /**
     * Get manufacturing orders for calendar view
     */
    public function calendar(GetCalendarRequest $request)
    {
        $validated = $request->validated();

        $orders = ManufacturingOrder::with(['product', 'bom'])
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
            ->get();

        return response()->json(['data' => $orders]);
    }

    /**
     * Reschedule a manufacturing order
     */
    public function reschedule(RescheduleManufacturingOrderRequest $request, ManufacturingOrder $manufacturingOrder)
    {
        try {
            $mo = $this->service->schedule($manufacturingOrder, $request->validated());

            return response()->json([
                'message' => 'Order rescheduled successfully',
                'data' => $mo
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Auto-schedule a manufacturing order based on capacity
     */
    public function autoSchedule(ManufacturingOrder $manufacturingOrder)
    {
        $schedulerService = app(\App\Services\SchedulerService::class);

        try {
            $schedule = $schedulerService->autoSchedule($manufacturingOrder);

            $mo = $this->service->schedule($manufacturingOrder, $schedule);

            return response()->json([
                'message' => 'Order scheduled successfully',
                'data' => $mo,
                'schedule_details' => $schedule,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get capacity data for work centers
     */
    public function capacity(GetCalendarRequest $request)
    {
        $validated = $request->validated();

        $schedulerService = app(\App\Services\SchedulerService::class);
        $workCenters = \App\Models\WorkCenter::all();

        $capacities = $workCenters->map(function ($wc) use ($schedulerService, $validated) {
            return $schedulerService->calculateCapacity(
                $wc,
                \Carbon\Carbon::parse($validated['start']),
                \Carbon\Carbon::parse($validated['end'])
            );
        });

        return response()->json(['data' => $capacities]);
    }
    public function reset(ManufacturingOrder $manufacturingOrder)
    {
        try {
            $mo = $this->service->resetToDraft($manufacturingOrder);
            return response()->json($mo);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
