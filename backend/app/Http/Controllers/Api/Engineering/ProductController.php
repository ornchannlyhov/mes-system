<?php

namespace App\Http\Controllers\Api\Engineering;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Engineering\StoreProductRequest;
use App\Http\Requests\Engineering\UpdateProductRequest;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends BaseController
{
    public function __construct(protected StockService $stockService) {}

    public function index(Request $request)
    {
        $query = Product::select([
            'id',
            'code',
            'name',
            'type',
            'uom',
            'cost',
            'image_url',
            'tracking',
            'is_active',
            'version',
            'created_at',
            'updated_at'
        ])
            ->applyStandardFilters(
                $request,
                ['code', 'name'],
                ['type', 'tracking', 'is_active']
            );

        $counts = $this->getStatusCounts(Product::query(), 'type');

        return $this->respondWithPagination(
            $query->paginate($request->get('per_page', 10)),
            ['counts' => $counts]
        );
    }

    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();
        $validated['is_active'] = true;

        if (!isset($validated['version'])) {
            $validated['version'] = '1.0';
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = $path;
        }

        $initialQty = (float) ($validated['initial_qty'] ?? 0);
        $locationId = $validated['location_id'] ?? null;

        $product = Product::create(\Illuminate\Support\Arr::except($validated, ['initial_qty', 'location_id']));

        if ($locationId) {
            $this->stockService->adjustStock([
                'product_id'  => $product->id,
                'location_id' => $locationId,
                'quantity'    => $initialQty,
                'reason'      => 'initial',
                'notes'       => "Initial stock for {$product->name}",
            ]);
        }

        return $this->success($product, [], 201);
    }

    public function show(Product $product)
    {
        $product->load(['boms', 'stocks.location', 'bomLines.bom']);

        $product->on_hand = $product->stocks->sum('quantity');

        $product->used_in_boms = $product->bomLines->map(function ($line) {
            return $line->bom;
        })->unique('id')->values();

        $product->recent_mos = $product->manufacturingOrders()
            ->latest()
            ->take(5)
            ->get();

        return $this->success($product);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $this->deleteImageFile($product->image_url);
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = $path;
        } elseif (array_key_exists('image_url', $validated) && $validated['image_url'] === null) {
            $this->deleteImageFile($product->image_url);
        }

        $product->update($validated);

        return $this->success($product);
    }

    public function destroy(Product $product)
    {
        if ($product->stocks()->where('quantity', '>', 0)->exists()) {
            return $this->error('Cannot delete product with existing stock.', 422);
        }

        $this->deleteImageFile($product->image_url);
        $product->delete();

        return $this->success(null, [], 204);
    }

    private function deleteImageFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
