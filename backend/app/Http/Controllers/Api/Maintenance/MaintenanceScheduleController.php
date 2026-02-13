<?php

namespace App\Http\Controllers\Api\Maintenance;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use App\Http\Requests\Maintenance\StoreMaintenanceScheduleRequest;
use App\Http\Requests\Maintenance\UpdateMaintenanceScheduleRequest;
use App\Models\MaintenanceSchedule;

class MaintenanceScheduleController extends BaseController
{
    public function index(Request $request)
    {
        $query = MaintenanceSchedule::select(['id', 'equipment_id', 'name', 'interval_days', 'next_maintenance', 'last_maintenance', 'is_active', 'created_at'])
            ->with(['equipment:id,name,code,status'])
            ->applyStandardFilters(
                $request,
                ['name', 'instructions'], // Searchable
                ['equipment_id', 'trigger_type', 'is_active'] // Filterable
            );

        return $this->respondWithPagination(
            $query->paginate($request->get('per_page', 10))
        );
    }

    public function store(StoreMaintenanceScheduleRequest $request)
    {
        $schedule = MaintenanceSchedule::create($request->validated());
        return $this->success($schedule, [], 201);
    }

    public function show(MaintenanceSchedule $schedule)
    {
        return $this->success(
            $schedule->load('equipment')
        );
    }

    public function update(UpdateMaintenanceScheduleRequest $request, MaintenanceSchedule $schedule)
    {
        $schedule->update($request->validated());
        return $this->success($schedule);
    }

    public function destroy(MaintenanceSchedule $schedule)
    {
        $schedule->delete();
        return $this->success(null, [], 204);
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

        return $this->success(
            $schedule->fresh()->load('equipment'),
            ['message' => 'Maintenance completed successfully']
        );
    }
}
