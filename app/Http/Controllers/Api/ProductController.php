<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = \App\Models\Product::query();

        if ($request->has('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        return \App\Http\Resources\ProductResource::collection($query->paginate(20));
    }

    /**
     * Search products across all shops
     */
    public function search(Request $request)
    {
        $query = \App\Models\Product::with('shop');

        // Search by product name or description
        if ($request->has('query')) {
            $search = $request->input('query');
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.description', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category') && $request->category !== 'All') {
            $query->where('category', $request->category);
        }

        // Location-based sorting
        if ($request->has(['latitude', 'longitude'])) {
            $lat = $request->latitude;
            $lng = $request->longitude;
            $radius = $request->input('radius', 50);

            // Join with shops table and calculate distance
            $query->join('shops', 'products.shop_id', '=', 'shops.id')
                ->selectRaw("products.*, ( 6371 * acos( cos( radians(?) ) * cos( radians( shops.latitude ) ) * cos( radians( shops.longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( shops.latitude ) ) ) ) AS distance", [$lat, $lng, $lat])
                ->having('distance', '<', $radius)
                ->orderBy('distance');
        } else {
            $query->latest();
        }

        return \App\Http\Resources\ProductResource::collection($query->paginate(20));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Ensure user has a shop
        $shop = $request->user()->shop;
        if (!$shop) {
            return response()->json(['message' => 'You do not have a shop'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'category' => 'required|string|max:255',
            'image_url' => 'nullable|url',
        ]);

        $product = $shop->products()->create($validated);

        return new \App\Http\Resources\ProductResource($product);
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\Product $product)
    {
        return new \App\Http\Resources\ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\Product $product)
    {
        // Ensure user owns the shop that owns the product
        if ($request->user()->id !== $product->shop->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric',
            'stock' => 'sometimes|integer',
            'category' => 'sometimes|required|string|max:255',
            'image_url' => 'nullable|url',
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return new \App\Http\Resources\ProductResource($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, \App\Models\Product $product)
    {
        if ($request->user()->id !== $product->shop->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
