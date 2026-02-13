<?php

namespace App\Http\Controllers\Api\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceLog;
use App\Http\Requests\Maintenance\LogMaintenanceRequest;
use Illuminate\Http\Request;

class MaintenanceLogController extends Controller
{
    public function index(Request $request)
    {
        $query = MaintenanceLog::select(['id', 'equipment_id', 'type', 'description', 'performed_by', 'performed_at', 'cost', 'created_at'])
            ->with(['equipment:id,name,code', 'performer:id,name']);

        if ($request->has('equipment_id')) {
            $query->where('equipment_id', $request->equipment_id);
        }

        return response()->json($query->orderBy('performed_at', 'desc')->paginate($request->get('per_page', 10)));
    }

    public function store(LogMaintenanceRequest $request)
    {
        $validated = $request->validated();

        $log = MaintenanceLog::create([
            'equipment_id' => $validated['equipment_id'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'actions_taken' => $validated['actions_taken'] ?? null,
            'cost' => $validated['cost'] ?? 0,
            'performed_by' => $request->user()->id,
            'performed_at' => now(),
        ]);

        // Schedule next maintenance
        $log->equipment->scheduleNextMaintenance();

        return response()->json($log->load(['equipment', 'performer']), 201);
    }

    public function show(MaintenanceLog $maintenanceLog)
    {
        return response()->json($maintenanceLog->load(['equipment', 'performer']));
    }

    public function update(LogMaintenanceRequest $request, MaintenanceLog $maintenanceLog)
    {
        $maintenanceLog->update($request->validated());

        return response()->json($maintenanceLog->load(['equipment', 'performer']));
    }

    public function destroy(MaintenanceLog $maintenanceLog)
    {
        $maintenanceLog->delete();

        return response()->json(null, 204);
    }
}
