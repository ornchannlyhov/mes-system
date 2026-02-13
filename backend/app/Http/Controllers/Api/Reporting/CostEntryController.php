<?php

namespace App\Http\Controllers\Api\Reporting;

use App\Http\Controllers\Api\BaseController;
use App\Models\CostEntry;
use App\Models\ManufacturingOrder;
use Illuminate\Http\Request;

class CostEntryController extends BaseController
{
    public function index(Request $request)
    {
        $query = CostEntry::select(['id', 'cost_type', 'manufacturing_order_id', 'product_id', 'quantity', 'unit_cost', 'total_cost', 'created_at'])
            ->with(['product:id,name,code,uom'])
            ->applyStandardFilters(
                $request,
                ['notes'], // Searchable
                ['cost_type', 'manufacturing_order_id', 'product_id'] // Filterable
            );

        return $this->respondWithPagination(
            $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 50))
        );
    }

    public function analysis(ManufacturingOrder $manufacturingOrder)
    {
        // Calculate cost analysis by aggregating all related CostEntries.
        $entries = CostEntry::where('manufacturing_order_id', $manufacturingOrder->id)->get();

        $summary = [
            'material' => $entries->where('cost_type', 'material')->sum('total_cost'),
            'labor' => $entries->where('cost_type', 'labor')->sum('total_cost'),
            'overhead' => $entries->where('cost_type', 'overhead')->sum('total_cost'),
            'scrap' => $entries->where('cost_type', 'scrap')->sum('total_cost'),
            'material_variance' => $entries->where('cost_type', 'material_variance')->sum('total_cost'),
        ];

        $summary['total'] = array_sum($summary);

        return $this->success([
            'manufacturing_order' => $manufacturingOrder->only('id', 'name', 'product_id', 'quantity_produced'),
            'summary' => $summary,
            'details' => $entries
        ]);
    }
}
