<?php

namespace App\Http\Controllers\Api\Reporting;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;

use App\Models\OeeRecord;


class OeeController extends BaseController
{
    public function dailyStats(Request $request)
    {
        $query = OeeRecord::with('workCenter')
            ->applyStandardFilters(
                $request,
                [],
                ['work_center_id']
            );

        if ($request->has('start_date')) {
            $query->where('record_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('record_date', '<=', $request->end_date);
        }

        return $this->respondWithPagination(
            $query->paginate($request->get('per_page', 10))
        );
    }
}
