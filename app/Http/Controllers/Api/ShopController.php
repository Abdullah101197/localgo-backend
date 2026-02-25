<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = \App\Models\Shop::query();

        // Search in shop name/description OR products
        if ($request->has('query')) {
            $search = $request->input('query');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('products', function ($productQuery) use ($search) {
                        $productQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('category')) {
            $category = $request->input('category');
            if ($category !== 'All') {
                $query->where('category', $category);
            }
        }

        // Distance-based sorting
        if ($request->has(['latitude', 'longitude'])) {
            $lat = $request->latitude;
            $lng = $request->longitude;
            $radius = $request->input('radius', 50); // Default 50km

            // Haversine formula for distance calculation
            $query->selectRaw("*, ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance", [$lat, $lng, $lat])
                ->withAvg('reviews', 'rating') // Calculate average rating for sorting
                ->having('distance', '<', $radius)
                ->orderBy('distance', 'asc')
                ->orderBy('reviews_avg_rating', 'desc');
        } else {
            $query->latest();
        }

        return \App\Http\Resources\ShopResource::collection($query->paginate(12));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'delivery_radius' => 'numeric',
            'image_url' => 'nullable|url',
        ]);

        $shop = $request->user()->shop()->create($validated);

        return new \App\Http\Resources\ShopResource($shop);
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\Shop $shop)
    {
        $shop->load('products');
        return new \App\Http\Resources\ShopResource($shop);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\Shop $shop)
    {
        // Ensure user owns the shop
        if ($request->user()->id !== $shop->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'address' => 'sometimes|required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'delivery_radius' => 'numeric',
            'image_url' => 'nullable|url',
            'is_active' => 'boolean',
        ]);

        $shop->update($validated);

        return new \App\Http\Resources\ShopResource($shop);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, \App\Models\Shop $shop)
    {
        if ($request->user()->id !== $shop->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $shop->delete();

        return response()->json([
            'message' => 'Shop deleted successfully'
        ]);
    }

    /**
     * Get top rated shops
     */
    public function topRated(Request $request)
    {
        $shops = \App\Models\Shop::where('is_active', 1)
            ->inRandomOrder()
            ->limit(3)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $shops
        ]);
    }

    public function categories(Request $request)
    {
        $categories = \App\Models\Shop::select('category')
            ->where('is_active', 1)
            ->groupBy('category')
            ->get()
            ->map(function ($shop) {
                $count = \App\Models\Shop::where('category', $shop->category)
                    ->where('is_active', 1)
                    ->count();

                return [
                    'name' => $shop->category,
                    'count' => $count
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}
