<?php

namespace App\Services;

use App\Models\WorkOrder;
use App\Models\OeeRecord;
use Carbon\Carbon;

class OeeService
{
    /**
     * Calculate OEE for a completed Work Order
     * Formula: Availability * Performance * Quality
     */
    public function calculate(WorkOrder $wo)
    {
        if ($wo->status !== 'done' || !$wo->actual_start || !$wo->finished_at) {
            return;
        }

        // 1. Availability Calculation
        $totalTimeMinutes = $wo->actual_start->diffInMinutes($wo->finished_at);
        $runTimeMinutes = $wo->duration_actual;

        $availability = ($totalTimeMinutes > 0) ? ($runTimeMinutes / $totalTimeMinutes) : 1.0;
        $availability = min($availability, 1.0);

        // 2. Performance Calculation
        $qtyProduced = $wo->quantity_produced;
        $scrapQty = $wo->scraps()->sum('quantity');
        $totalCount = $qtyProduced + $scrapQty;

        // Determine Ideal Cycle Time (minutes per unit)
        // Prefer explicit standard if available, otherwise derive from planned values
        $plannedDuration = $wo->duration_expected ?? 0;
        $plannedQty = $wo->manufacturingOrder->qty_to_produce > 0 ? $wo->manufacturingOrder->qty_to_produce : 1;
        $idealCycleTime = $plannedDuration / $plannedQty;

        if ($runTimeMinutes > 0 && $idealCycleTime > 0) {
            $performance = ($idealCycleTime * $totalCount) / $runTimeMinutes;
        } else {
            $performance = 1.0;
        }

        // 3. Quality Calculation
        $quality = ($totalCount > 0) ? ($qtyProduced / $totalCount) : 1.0;

        // Daily Aggregation
        $record = OeeRecord::firstOrNew([
            'work_center_id' => $wo->work_center_id,
            'record_date' => today(),
        ]);

        // Initialize defaults if new
        if (!$record->exists) {
            $record->planned_time_minutes = 0;
            $record->actual_runtime_minutes = 0;
            $record->total_standard_minutes = 0;
            $record->downtime_minutes = 0;
            $record->total_units_produced = 0;
            $record->good_units = 0;
            $record->defect_units = 0;
            $record->ideal_cycle_time = $idealCycleTime;
        }

        // Calculate Standard Minutes for this WO
        // Standard Minutes = Total Units * Ideal Cycle Time
        $standardMinutes = $totalCount * $idealCycleTime;

        // Update Totals
        $record->planned_time_minutes += $totalTimeMinutes;
        $record->actual_runtime_minutes += $runTimeMinutes;
        $record->total_standard_minutes += $standardMinutes;
        $record->downtime_minutes += ($totalTimeMinutes - $runTimeMinutes);

        $record->total_units_produced += $totalCount;
        $record->good_units += $qtyProduced;
        $record->defect_units += $scrapQty;

        // Calculate Daily Scores

        // Availability Score
        if ($record->planned_time_minutes > 0) {
            $record->availability_score = ($record->actual_runtime_minutes / $record->planned_time_minutes) * 100;
        } else {
            // If planned time is 0 but we have runtime (edge case), score is 100
            $record->availability_score = ($record->actual_runtime_minutes > 0) ? 100 : 0;
        }

        // Quality Score
        if ($record->total_units_produced > 0) {
            $record->quality_score = ($record->good_units / $record->total_units_produced) * 100;
        } else {
            $record->quality_score = 100;
        }

        // Performance Score
        // Performance = (Total Standard Minutes Produced / Total Run Time) * 100
        if ($record->actual_runtime_minutes > 0) {
            $record->performance_score = ($record->total_standard_minutes / $record->actual_runtime_minutes) * 100;
        } else {
            $record->performance_score = 100;
        }

        // Cap individual scores BEFORE calculating final OEE
        $record->availability_score = min($record->availability_score, 100);
        $record->quality_score = min($record->quality_score, 100);
        // Performance can technically exceed 100% (faster than expected), cap it
        $record->performance_score = min($record->performance_score, 100);

        // Final OEE Score (all scores are 0-100, so divide by 10000 to get 0-100 result)
        $record->oee_score = ($record->availability_score * $record->performance_score * $record->quality_score) / 10000;

        $record->save();
    }
}
