<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Display a listing of reviews for a product.
     */
    public function index(Product $product)
    {
        $reviews = $product->reviews()
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    /**
     * Store a newly created review in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $order = Order::findOrFail($validated['order_id']);

        // Check ownership and status
        if ($order->customer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($order->status !== 'delivered') {
            return response()->json(['message' => 'You can only review products from delivered orders.'], 400);
        }

        // Verify product is in order
        $hasProduct = $order->items()->where('product_id', $validated['product_id'])->exists();
        if (!$hasProduct) {
            return response()->json(['message' => 'Product not found in this order.'], 400);
        }

        // Check if already reviewed
        $exists = Review::where('order_id', $order->id)
            ->where('product_id', $validated['product_id'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'You have already reviewed this product for this order.'], 400);
        }

        $review = Review::create([
            'user_id' => $request->user()->id,
            'order_id' => $order->id,
            'product_id' => $validated['product_id'],
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return response()->json($review, 201);
    }
}
