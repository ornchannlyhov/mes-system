<?php

namespace App\Http\Controllers\Api\Reporting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\OeeRecord;


class OeeController extends Controller
{

    public function dailyStats(Request $request)
    {
        $query = OeeRecord::with('workCenter');

        if ($request->has('work_center_id')) {
            $query->where('work_center_id', $request->work_center_id);
        }

        if ($request->has('start_date')) {
            $query->where('record_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('record_date', '<=', $request->end_date);
        }

        return response()->json($query->orderBy('record_date', 'desc')->paginate($request->get('per_page', 10)));
    }
}
