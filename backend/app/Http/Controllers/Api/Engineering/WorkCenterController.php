<?php

namespace App\Http\Controllers\Api\Engineering;

use App\Http\Controllers\Controller;
use App\Http\Requests\Engineering\StoreWorkCenterRequest;
use App\Http\Requests\Engineering\UpdateWorkCenterRequest;
use App\Models\WorkCenter;
use Illuminate\Http\Request;

class WorkCenterController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkCenter::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $workCenters = $query->orderBy('name')->get();

        return response()->json(['data' => $workCenters]);
    }

    public function store(StoreWorkCenterRequest $request)
    {
        $workCenter = WorkCenter::create($request->validated());

        return response()->json($workCenter, 201);
    }

    public function show(WorkCenter $workCenter)
    {
        return response()->json(
            $workCenter->load(['operations', 'equipment'])
        );
    }

    public function update(UpdateWorkCenterRequest $request, WorkCenter $workCenter)
    {
        $validated = $request->validated();

        $workCenter->update($validated);

        return response()->json($workCenter);
    }

    public function destroy(WorkCenter $workCenter)
    {
        $workCenter->delete();

        return response()->json(null, 204);
    }

}