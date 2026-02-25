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
                ->selectRaw("products.*, shops.name as shop_name, ( 6371 * acos( cos( radians(?) ) * cos( radians( shops.latitude ) ) * cos( radians( shops.longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( shops.latitude ) ) ) ) AS distance", [$lat, $lng, $lat])
                ->having('distance', '<', $radius)
                ->orderBy('distance', 'asc')
                // Secondary sort: High rated shops first (if we had a column, for now we stick to distance as primary)
                // ->orderBy('shops.average_rating', 'desc') 
            ;

            // Re-apply search filter after join to avoid ambiguous column errors
            if ($request->has('query')) {
                $search = $request->input('query');
                $query->where(function ($q) use ($search) {
                    $q->where('products.name', 'like', "%{$search}%")
                        ->orWhere('products.description', 'like', "%{$search}%")
                        ->orWhere('products.keywords', 'like', "%{$search}%");
                });
            }
        } else {
            // Standard search without location
            if ($request->has('query')) {
                $search = $request->input('query');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        // Improved keyword search to handle comma-separated values
                        ->orWhere('keywords', 'like', "{$search}%")      // Starts with
                        ->orWhere('keywords', 'like', "% {$search}%")    // After space
                        ->orWhere('keywords', 'like', "%,{$search}%")    // After comma
                        ->orWhere('keywords', 'like', "%, {$search}%")   // After comma space
                        // Fuzzy search for typos (e.g., pandol -> panadol)
                        ->orWhereRaw("SOUNDEX(name) = SOUNDEX(?)", [$search])
                        ->orWhereHas('shop', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            }
            $query->latest();
        }

        return \App\Http\Resources\ProductResource::collection($query->paginate(20));
    }

    public function suggestions(Request $request)
    {
        $query = $request->input('query');
        if (!$query || strlen($query) < 2)
            return response()->json([]);

        // Get matching products with images
        $products = \App\Models\Product::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhereRaw("SOUNDEX(name) = SOUNDEX(?)", [$query]);
        })
            ->select('name', 'image_url')
            ->limit(5)
            ->get()
            ->map(function ($p) {
                return [
                    'name' => $p->name,
                    'image_url' => $p->image_url,
                    'type' => 'product'
                ];
            });

        // Get matching tokens from keywords
        $keywordMatches = \App\Models\Product::where('keywords', 'like', "%{$query}%")
            ->select('keywords')
            ->limit(10)
            ->get()
            ->pluck('keywords')
            ->flatMap(function ($k) {
                return explode(',', $k);
            })
            ->map(function ($k) {
                return trim($k);
            })
            ->filter(function ($k) use ($query) {
                return stripos($k, $query) !== false;
            })
            ->unique()
            ->take(3)
            ->map(function ($k) {
                return [
                    'name' => $k,
                    'type' => 'keyword'
                ];
            });

        // Get matching shop names
        $shopMatches = \App\Models\Shop::where('name', 'like', "%{$query}%")
            ->select('name', 'image_url')
            ->limit(3)
            ->get()
            ->map(function ($s) {
                return [
                    'name' => $s->name,
                    'image_url' => $s->image_url,
                    'type' => 'shop'
                ];
            });

        $suggestions = $products->concat($keywordMatches)->concat($shopMatches)->unique('name')->values();

        return response()->json($suggestions);
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

    /**
     * Get featured products for "Just for You" section
     */
    public function featured(Request $request)
    {
        $products = \App\Models\Product::with('shop')
            ->where('is_active', 1)
            ->inRandomOrder()
            ->limit(8)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Get flash sale products
     */
    public function flashSale(Request $request)
    {
        $products = \App\Models\Product::with('shop')
            ->where('is_active', 1)
            ->inRandomOrder()
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
}
