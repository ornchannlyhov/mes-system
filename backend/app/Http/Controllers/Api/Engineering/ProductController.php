<?php

namespace App\Http\Controllers\Api\Engineering;

use App\Http\Controllers\Controller;
use App\Http\Requests\Engineering\StoreProductRequest;
use App\Http\Requests\Engineering\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        // Filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('tracking')) {
            $query->where('tracking', $request->tracking);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        if ($request->has('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(code) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
            });
        }

        return response()->json(
            $query->orderBy('name')->paginate($request->get('per_page', 10))
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

        return response()->json($product, 201);
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

        return response()->json($product);
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

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
