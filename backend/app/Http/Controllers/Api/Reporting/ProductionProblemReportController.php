<?php

namespace App\Http\Controllers\Api\Reporting;

use App\Http\Controllers\Controller;
use App\Models\Scrap;
use App\Models\Consumption;
use App\Models\UnbuildOrder;
use Illuminate\Support\Facades\DB;

class ProductionProblemReportController extends Controller
{
    public function index()
    {
        // 1. Scrap Stats
        $scrapStats = Scrap::selectRaw('
            COUNT(*) as total_count,
            SUM(quantity) as total_quantity
        ')->first();

        // Calculate total scrap cost (requires join with products)
        // Since cost is not stored on scrap, we multiply quantity * product_cost
        $totalScrapCost = Scrap::join('products', 'scraps.product_id', '=', 'products.id')
            ->sum(DB::raw('scraps.quantity * products.cost'));

        // Top Scrap Reason
        $topReason = Scrap::select('reason', DB::raw('COUNT(*) as count'))
            ->groupBy('reason')
            ->orderByDesc('count')
            ->limit(1)
            ->value('reason');

        // 2. Variance Stats
        // Variance cost impact is stored directly on consumption
        $varianceStats = Consumption::whereRaw('qty_consumed != qty_planned')
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN cost_impact > 0 THEN cost_impact ELSE 0 END) as total_loss,
                SUM(CASE WHEN cost_impact < 0 THEN ABS(cost_impact) ELSE 0 END) as total_savings,
                SUM(CASE WHEN (qty_consumed - qty_planned) > 0 THEN 1 ELSE 0 END) as over_consumption_count
            ')->first();

        // 3. Unbuild Stats
        $unbuildStats = UnbuildOrder::selectRaw("
            COUNT(*) as total_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done_count,
            SUM(quantity) as total_quantity
        ")->first();

        return response()->json([
            'scrap' => [
                'count' => $scrapStats->total_count ?? 0,
                'quantity' => $scrapStats->total_quantity ?? 0,
                'cost' => $totalScrapCost ?? 0,
                'top_reason' => $topReason,
            ],
            'variance' => [
                'count' => $varianceStats->total_count ?? 0,
                'cost' => $varianceStats->total_loss ?? 0,
                'savings' => $varianceStats->total_savings ?? 0,
                'over_consumption_count' => $varianceStats->over_consumption_count ?? 0,
            ],
            'unbuild' => [
                'count' => $unbuildStats->total_count ?? 0,
                'pending' => $unbuildStats->pending_count ?? 0,
                'completed' => $unbuildStats->done_count ?? 0,
                'quantity' => $unbuildStats->total_quantity ?? 0,
            ]
        ]);
    }
}
