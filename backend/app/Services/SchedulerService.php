<?php

namespace App\Services;

use App\Models\ManufacturingOrder;
use App\Models\WorkCenter;
use Carbon\Carbon;

class SchedulerService
{
    /**
     * Auto-schedule a manufacturing order based on available capacity
     */
    public function autoSchedule(ManufacturingOrder $mo): array
    {
        $bom = $mo->bom()->with('operations.workCenter')->first();

        if (!$bom || $bom->operations->isEmpty()) {
            throw new \InvalidArgumentException('Manufacturing order must have a BOM with operations');
        }

        $scheduledStart = Carbon::now()->addDay(); // Start tomorrow by default
        $operations = $bom->operations->sortBy('sequence');
        $scheduleDetails = [];

        foreach ($operations as $operation) {
            $duration = $operation->duration_minutes / 60; // Convert to hours

            // Find next available slot for this work center
            $slot = $this->findNextAvailableSlot(
                $operation->workCenter,
                $scheduledStart,
                $duration
            );

            $scheduleDetails[] = [
                'operation' => $operation->name,
                'work_center' => $operation->workCenter->name,
                'start' => $slot['start']->toIso8601String(),
                'end' => $slot['end']->toIso8601String(),
                'duration_hours' => $duration,
            ];

            // Next operation starts after this one ends
            $scheduledStart = $slot['end'];
        }

        // Update MO with calculated schedule
        $totalStart = Carbon::parse($scheduleDetails[0]['start']);
        $totalEnd = Carbon::parse(end($scheduleDetails)['end']);

        return [
            'scheduled_start' => $totalStart,
            'scheduled_end' => $totalEnd,
            'operations' => $scheduleDetails,
            'total_duration_hours' => $totalStart->diffInHours($totalEnd),
        ];
    }

    /**
     * Find next available time slot for a work center
     */
    protected function findNextAvailableSlot(WorkCenter $workCenter, Carbon $startFrom, float $durationHours): array
    {
        $startHour = 8; // Work starts at 8 AM
        $endHour = 17; // Work ends at 5 PM

        // Get all MOs scheduled on this work center
        ManufacturingOrder::whereHas('bom.operations', function ($query) use ($workCenter) {
            $query->where('work_center_id', $workCenter->id);
        })
            ->whereNotNull('scheduled_start')
            ->whereNotNull('scheduled_end')
            ->where(function ($query) use ($startFrom) {
                $query->where('scheduled_end', '>=', $startFrom);
            })
            ->orderBy('scheduled_start')
            ->get();

        // Simple algorithm: try to fit in the next working day
        $candidateStart = $startFrom->copy()->hour($startHour)->minute(0)->second(0);

        // If start time is after working hours, move to next day
        if ($candidateStart->hour >= $endHour) {
            $candidateStart->addDay()->hour($startHour)->minute(0);
        }

        $candidateEnd = $candidateStart->copy()->addHours($durationHours);

        // Check if it fits within working hours
        if ($candidateEnd->hour > $endHour || ($candidateEnd->hour == $endHour && $candidateEnd->minute > 0)) {
            // Spills over to next day - for simplicity, just push to next day
            $candidateStart->addDay()->hour($startHour)->minute(0);
            $candidateEnd = $candidateStart->copy()->addHours($durationHours);
        }

        return [
            'start' => $candidateStart,
            'end' => $candidateEnd,
        ];
    }

    /**
     * Calculate work center capacity utilization for a date range
     */
    public function calculateCapacity(WorkCenter $workCenter, Carbon $start, Carbon $end): array
    {
        $workingHoursPerDay = 8;
        $totalDays = $start->diffInDays($end) + 1;
        $totalCapacityHours = $totalDays * $workingHoursPerDay;

        // Get scheduled hours for this work center
        $scheduledHours = ManufacturingOrder::whereHas('bom.operations', function ($query) use ($workCenter) {
            $query->where('work_center_id', $workCenter->id);
        })
            ->whereNotNull('scheduled_start')
            ->where('scheduled_start', '>=', $start)
            ->where('scheduled_end', '<=', $end)
            ->get()
            ->sum(function ($mo) {
                return $mo->scheduled_start->diffInHours($mo->scheduled_end);
            });

        $utilizationPercent = $totalCapacityHours > 0
            ? ($scheduledHours / $totalCapacityHours) * 100
            : 0;

        return [
            'work_center' => $workCenter->name,
            'total_capacity_hours' => $totalCapacityHours,
            'scheduled_hours' => $scheduledHours,
            'available_hours' => $totalCapacityHours - $scheduledHours,
            'utilization_percent' => round($utilizationPercent, 2),
        ];
    }
}
