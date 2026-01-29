<?php

namespace App\Services;

use App\Models\WorkOrder;
use App\Models\CostEntry;
use App\Models\QualityCheckResult;
use Illuminate\Support\Facades\DB;

class WorkOrderService
{
    public function __construct(
        protected OeeService $oeeService
    ) {
    }

    /**
     * Start the work order timer
     */
    public function start(WorkOrder $workOrder): WorkOrder
    {
        if ($workOrder->status !== 'ready') {
            throw new \InvalidArgumentException('Work order must be in ready status to start');
        }

        if (!$workOrder->actual_start) {
            $workOrder->actual_start = now();
        }
        $workOrder->start();
        $workOrder->save();

        return $workOrder;
    }

    /**
     * Pause the work order
     */
    public function pause(WorkOrder $workOrder): WorkOrder
    {
        if ($workOrder->status !== 'in_progress') {
            throw new \InvalidArgumentException('Work order must be in progress to pause');
        }

        // Calculate elapsed time since started_at and add to duration_actual
        $elapsed = 0;
        if ($workOrder->started_at) {
            $elapsed = $workOrder->started_at->diffInMinutes(now());
        }

        $workOrder->update([
            'status' => 'paused',
            'duration_actual' => ($workOrder->duration_actual ?? 0) + $elapsed,
            'started_at' => null,
        ]);

        return $workOrder;
    }

    /**
     * Resume the work order
     */
    public function resume(WorkOrder $workOrder): WorkOrder
    {
        if ($workOrder->status !== 'paused') {
            throw new \InvalidArgumentException('Work order must be paused to resume');
        }

        $workOrder->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return $workOrder;
    }

    /**
     * Finish the work order
     */
    public function finish(WorkOrder $workOrder, ?float $qtyProduced = null): WorkOrder
    {
        if (!in_array($workOrder->status, ['in_progress', 'paused', 'ready'])) {
            // Allow 'ready' for instant-finish scenarios (like in Seeder or simple flows)
            // but ideally should be in_progress. For strictness we might restrict this.
            // Let's allow 'ready' if we set actual_start now.
        }

        return DB::transaction(function () use ($workOrder, $qtyProduced) {
            // Calculate duration safely
            $duration = 0;
            if ($workOrder->started_at) {
                $duration = $workOrder->started_at->diffInMinutes(now());
            }

            if ($qtyProduced === null) {
                $qtyProduced = $workOrder->manufacturingOrder->qty_to_produce;
            }

            // If finishing immediately from ready (e.g. seeder), set actual_start
            $updates = [
                'status' => 'done',
                'finished_at' => now(),
                'duration_actual' => ($workOrder->duration_actual ?? 0) + $duration,
                'quantity_produced' => $qtyProduced,
            ];

            if (!$workOrder->actual_start) {
                $updates['actual_start'] = now();
            }

            $workOrder->update($updates);

            $workCenter = $workOrder->workCenter;
            $hours = $workOrder->duration_actual / 60;

            // 1. Calculate Labor Cost
            $costPerHour = $workCenter->cost_per_hour ?? 0;
            $totalLaborCost = $hours * $costPerHour;

            if ($totalLaborCost > 0) {
                CostEntry::create([
                    'manufacturing_order_id' => $workOrder->manufacturing_order_id,
                    'work_order_id' => $workOrder->id,
                    'cost_type' => 'labor',
                    'quantity' => $hours,
                    'unit_cost' => $costPerHour,
                    'total_cost' => $totalLaborCost,
                    'notes' => 'Labor: ' . number_format($hours, 2) . ' hrs @ $' . $costPerHour . '/hr',
                    'created_at' => now(),
                ]);
            }

            // 2. Calculate Overhead Cost
            $overheadRate = $workCenter->overhead_per_hour ?? 0;
            $totalOverheadCost = $hours * $overheadRate;

            if ($totalOverheadCost > 0) {
                CostEntry::create([
                    'manufacturing_order_id' => $workOrder->manufacturing_order_id,
                    'work_order_id' => $workOrder->id,
                    'cost_type' => 'overhead',
                    'quantity' => $hours,
                    'unit_cost' => $overheadRate,
                    'total_cost' => $totalOverheadCost,
                    'notes' => 'Overhead: ' . number_format($hours, 2) . ' hrs @ $' . $overheadRate . '/hr',
                    'created_at' => now(),
                ]);
            }

            // 2. Calculate OEE
            $this->oeeService->calculate($workOrder);

            // 3. Update MO Progress if this is the last WO
            $isLast = !WorkOrder::where('manufacturing_order_id', $workOrder->manufacturing_order_id)
                ->where('sequence', '>', $workOrder->sequence)
                ->exists();

            if ($isLast) {
                $mo = $workOrder->manufacturingOrder;

                // Update quantity produced
                $mo->update(['qty_produced' => $qtyProduced]);

                // Auto-complete if target quantity reached or exceeded
                if ($qtyProduced >= $mo->qty_to_produce) {
                    $mo->update([
                        'status' => 'done',
                        'actual_end' => now()
                    ]);
                }
            }

            // 4. Set next work order to ready
            $nextWo = WorkOrder::where('manufacturing_order_id', $workOrder->manufacturing_order_id)
                ->where('sequence', '>', $workOrder->sequence)
                ->where('status', 'pending')
                ->orderBy('sequence')
                ->first();

            if ($nextWo) {
                $nextWo->update(['status' => 'ready']);
            }

            return $workOrder;
        });
    }
}
