<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Inventory\AdjustStockRequest;
use App\Http\Requests\Inventory\StoreLocationRequest;
use App\Http\Requests\Inventory\UpdateLocationRequest;
use App\Models\Location;
use App\Models\Stock;
use Illuminate\Http\Request;

class LocationController extends BaseController
{
    protected $stockService;

    public function __construct(\App\Services\StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $query = Location::query()->applyStandardFilters(
            $request,
            ['name', 'code'],
            ['type', 'organization_id']
        );

        $counts = $this->getStatusCounts(Location::query(), 'type');

        return $this->respondWithPagination(
            $query->paginate($request->get('per_page', 10)),
            ['counts' => $counts]
        );
    }

    public function store(StoreLocationRequest $request)
    {
        $location = Location::create($request->validated());

        return $this->success($location, [], 201);
    }

    public function show(Location $location)
    {
        return $this->success(
            $location->load(['stocks.product', 'stocks.lot'])
        );
    }

    public function update(UpdateLocationRequest $request, Location $location)
    {
        $location->update($request->validated());

        return $this->success($location);
    }

    public function destroy(Location $location)
    {
        if ($location->stocks()->exists()) {
            return $this->error('Cannot delete location with existing stock.', 422);
        }

        $location->delete();

        return $this->success(null, [], 204);
    }

    // Get stock for a location
    public function stock(Location $location, Request $request)
    {
        $query = Stock::where('location_id', $location->id)
            ->with(['product', 'lot']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        return $this->success($query->get());
    }

    // Adjust stock
    public function adjustStock(Location $location, AdjustStockRequest $request)
    {
        $stock = $this->stockService->adjust($location, $request->validated());
        return $this->success($stock);
    }
}
