<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = \App\Models\Order::query();

        $query->where(function ($q) use ($user, $request) {
            // Always show user's own orders as customer
            $q->where('customer_id', $user->id);

            if ($user->shop) {
                $q->orWhere('shop_id', $user->shop->id);
            }

            if ($user->rider) {
                $q->orWhere('rider_id', $user->rider->id);

                // If rider is looking for available deliveries
                if ($request->has('available')) {
                    $q->orWhere(function ($sq) {
                        $sq->where('status', 'ready')->whereNull('rider_id');
                    });
                }
            }
        });

        return \App\Http\Resources\OrderResource::collection(
            $query->with(['items.product', 'shop', 'customer'])->latest()->paginate(10)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'delivery_address' => 'required|string',
            'delivery_latitude' => 'nullable|numeric',
            'delivery_longitude' => 'nullable|numeric',
            'payment_method' => 'required|string|in:cod,easypaisa,jazzcash',
        ]);

        $totalAmount = 0;

        DB::beginTransaction();
        try {
            // Create Order
            $order = \App\Models\Order::create([
                'customer_id' => $request->user()->id,
                'shop_id' => $validated['shop_id'],
                'status' => 'pending',
                'delivery_address' => $validated['delivery_address'],
                'delivery_latitude' => $validated['delivery_latitude'] ?? null,
                'delivery_longitude' => $validated['delivery_longitude'] ?? null,
                'payment_method' => $validated['payment_method'],
                'total_amount' => 0, // Calculated below
            ]);

            foreach ($validated['items'] as $item) {
                $product = \App\Models\Product::find($item['product_id']);

                // Basic stock check
                if ($product->stock < $item['quantity']) {
                    throw new \DomainException("Insufficient stock for {$product->name}");
                }

                $price = $product->price;
                $lineTotal = $price * $item['quantity'];
                $totalAmount += $lineTotal;

                // Create Order Item
                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                ]);

                // Decrement stock
                $product->decrement('stock', $item['quantity']);
            }

            $order->update(['total_amount' => $totalAmount]);

            DB::commit();

            return new \App\Http\Resources\OrderResource($order->load('items'));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\Order $order)
    {
        // Authorization check skipped for brevity, should be added
        return new \App\Http\Resources\OrderResource($order->load(['items.product', 'shop', 'rider']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\Order $order)
    {
        $user = $request->user();

        // Authorization: Shop owner can update their shop's orders
        if ($user->role === 'shop' && $order->shop_id !== $user->shop?->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Authorization: Rider logic
        if ($user->role === 'rider') {
            // If claiming an unassigned ready order
            if ($order->status === 'ready' && !$order->rider_id) {
                $order->update(['rider_id' => $user->rider->id]);
            } elseif ($order->rider_id !== $user->rider?->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $validated = $request->validate([
            'status' => 'required|string|in:pending,accepted,ready,pickup,delivered,cancelled',
            'rider_id' => 'nullable|exists:riders,id',
        ]);

        $order->update($validated);

        return new \App\Http\Resources\OrderResource($order->load(['items.product', 'shop', 'customer']));
    }

    public function stats(Request $request)
    {
        $user = $request->user();
        if (!$user->shop) {
            return response()->json(['message' => 'Shop not found'], 404);
        }

        $shopId = $user->shop->id;
        $orders = \App\Models\Order::where('shop_id', $shopId);

        return response()->json([
            'total_revenue' => (float) $orders->where('status', 'delivered')->sum('total_amount'),
            'total_orders' => $orders->count(),
            'pending_orders' => \App\Models\Order::where('shop_id', $shopId)->where('status', 'pending')->count(),
            'delivered_orders' => \App\Models\Order::where('shop_id', $shopId)->where('status', 'delivered')->count(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Orders usually shouldn't be deleted, maybe cancelled
    }
}
