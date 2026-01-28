<?php

namespace App\Services;

use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceRequest;
use Carbon\Carbon;

class MaintenanceSchedulerService
{
    /**
     * Check schedules and generate requests if due
     */
    public function generateDueRequests()
    {
        // 1. Get Due Schedules
        $dueSchedules = MaintenanceSchedule::where('next_due_date', '<=', now())
            ->where('is_active', true)
            ->get();

        $generated = [];

        foreach ($dueSchedules as $schedule) {
            // Check if a request already exists for this equipment/schedule pending?
            // Optional check to avoid duplicate flooding

            $request = MaintenanceRequest::create([
                'name' => $schedule->name, // Or generate unique name
                'equipment_id' => $schedule->equipment_id,
                'status' => 'pending',
                'priority' => 'normal', // Could be from schedule
                'description' => $schedule->description . ' (Auto-scheduled)',
                'requested_by' => 1, // System user or schedule owner
                'due_date' => now()->addDays(7), // Default deadline
            ]);

            // Update Schedule next run
            if ($schedule->frequency_type === 'days') {
                $schedule->update([
                    'last_run_date' => now(),
                    'next_due_date' => now()->addDays($schedule->interval)
                ]);
            } else if ($schedule->frequency_type === 'months') {
                $schedule->update([
                    'last_run_date' => now(),
                    'next_due_date' => now()->addMonths($schedule->interval)
                ]);
            }

            $generated[] = $request;
        }

        return $generated;
    }
}
