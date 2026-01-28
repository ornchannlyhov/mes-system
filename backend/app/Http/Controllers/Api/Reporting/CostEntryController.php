<?php

namespace App\Http\Controllers\Api\Reporting;

use App\Http\Controllers\Controller;
use App\Models\CostEntry;
use App\Models\ManufacturingOrder;
use Illuminate\Http\Request;

class CostEntryController extends Controller
{
    public function index()
    {
        $entries = CostEntry::with('product')->latest()->limit(50)->get();
        return response()->json(['data' => $entries]);
    }

    public function analysis(ManufacturingOrder $manufacturingOrder)
    {
        // For now, simpler implementation calculating from CostEntries
        // In future, we might aggregate 'TimeLogs' and 'StockMoves'

        $entries = CostEntry::where('manufacturing_order_id', $manufacturingOrder->id)->get();

        $summary = [
            'material' => $entries->where('cost_type', 'material')->sum('total_cost'),
            'labor' => $entries->where('cost_type', 'labor')->sum('total_cost'),
            'overhead' => $entries->where('cost_type', 'overhead')->sum('total_cost'),
            'scrap' => $entries->where('cost_type', 'scrap')->sum('total_cost'),
            'material_variance' => $entries->where('cost_type', 'material_variance')->sum('total_cost'),
        ];

        $summary['total'] = array_sum($summary);

        return response()->json([
            'manufacturing_order' => $manufacturingOrder->only('id', 'name', 'product_id', 'quantity_produced'),
            'summary' => $summary,
            'details' => $entries
        ]);
    }
}
