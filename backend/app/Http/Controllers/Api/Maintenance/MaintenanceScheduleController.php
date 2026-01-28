<?php

namespace App\Http\Controllers\Api\Maintenance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Maintenance\StoreMaintenanceScheduleRequest;
use App\Http\Requests\Maintenance\UpdateMaintenanceScheduleRequest;
use App\Models\MaintenanceSchedule;

class MaintenanceScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = MaintenanceSchedule::with('equipment');
        return response()->json($query->paginate($request->get('per_page', 10)));
    }

    public function store(StoreMaintenanceScheduleRequest $request)
    {
        $schedule = MaintenanceSchedule::create($request->validated());
        return response()->json($schedule, 201);
    }

    public function show(MaintenanceSchedule $schedule)
    {
        return response()->json($schedule->load('equipment'));
    }

    public function update(UpdateMaintenanceScheduleRequest $request, MaintenanceSchedule $schedule)
    {
        $schedule->update($request->validated());
        return response()->json($schedule);
    }

    public function destroy(MaintenanceSchedule $schedule)
    {
        $schedule->delete();
        return response()->noContent();
    }

    /**
     * Complete a maintenance schedule - marks maintenance as done and schedules next
     */
    public function complete(MaintenanceSchedule $schedule)
    {
        $today = now()->startOfDay();

        // Update the schedule with last completed date and calculate next maintenance
        $nextMaintenance = $today->copy()->addDays($schedule->interval_days);

        $schedule->update([
            'last_maintenance' => $today,
            'next_maintenance' => $nextMaintenance,
        ]);

        // Also update the equipment's status and maintenance dates
        if ($schedule->equipment) {
            $schedule->equipment->update([
                'last_maintenance' => $today,
                'next_maintenance' => $nextMaintenance,
                'status' => 'operational',
            ]);
        }

        return response()->json([
            'message' => 'Maintenance completed successfully',
            'schedule' => $schedule->fresh()->load('equipment'),
        ]);
    }
}
