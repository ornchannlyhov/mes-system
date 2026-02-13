<?php

namespace App\Http\Controllers\Api\Engineering;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Engineering\StoreProductRequest;
use App\Http\Requests\Engineering\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends BaseController
{
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
                ['code', 'name'], // Searchable fields
                ['type', 'tracking', 'is_active'] // Exact match filters
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

        $product = Product::create($validated);

        return $this->success($product, [], 201);
    }

    public function show(Product $product)
    {
        $product->load(['boms', 'stocks.location', 'bomLines.bom']);

        $product->on_hand = $product->stocks->sum('quantity');

        // Boms where this product is used as a component
        $product->used_in_boms = $product->bomLines->map(function ($line) {
            return $line->bom;
        })->unique('id')->values();

        // Recent MOs where this product is being produced
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
            // Delete old image if exists
            if ($product->image_url && \Illuminate\Support\Facades\Storage::disk('public')->exists($product->image_url)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image_url);
            }

            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = $path;
        }

        $product->update($validated);

        return $this->success($product);
    }

    public function destroy(Product $product)
    {
        // Optional: Check for dependencies before deleting
        if ($product->stocks()->where('quantity', '>', 0)->exists()) {
            return $this->error('Cannot delete product with existing stock.', 422);
        }

        $product->delete();

        return $this->success(null, [], 204);
    }
}
