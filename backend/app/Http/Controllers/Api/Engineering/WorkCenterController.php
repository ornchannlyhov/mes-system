<?php

namespace App\Http\Controllers\Api\Engineering;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Engineering\StoreWorkCenterRequest;
use App\Http\Requests\Engineering\UpdateWorkCenterRequest;
use App\Models\WorkCenter;
use Illuminate\Http\Request;

class WorkCenterController extends BaseController
{
    public function index(Request $request)
    {
        $query = WorkCenter::query()->applyStandardFilters(
            $request,
            ['name', 'code'], // Searchable
            ['status', 'organization_id'] // Filterable
        );

        $counts = $this->getStatusCounts(WorkCenter::query(), 'status');

        return $this->respondWithPagination(
            $query->paginate($request->get('per_page', 10)),
            ['counts' => $counts]
        );
    }

    public function store(StoreWorkCenterRequest $request)
    {
        $workCenter = WorkCenter::create($request->validated());

        return $this->success($workCenter, [], 201);
    }

    public function show(WorkCenter $workCenter)
    {
        return $this->success(
            $workCenter->load(['operations', 'equipment'])
        );
    }

    public function update(UpdateWorkCenterRequest $request, WorkCenter $workCenter)
    {
        $workCenter->update($request->validated());

        return $this->success($workCenter);
    }

    public function destroy(WorkCenter $workCenter)
    {
        if ($workCenter->operations()->exists()) {
            return $this->error('Cannot delete work center with assigned operations.', 422);
        }

        $workCenter->delete();

        return $this->success(null, [], 204);
    }

}