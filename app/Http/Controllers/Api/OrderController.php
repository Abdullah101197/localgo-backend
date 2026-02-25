<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;
use Stripe\Refund;
use Stripe\Stripe;

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
            $query->with(['items.product', 'shop', 'customer'])
                ->withCount([
                    'messages' => function ($q) use ($user) {
                        $q->where('sender_id', '!=', $user->id)->where('is_read', false);
                    }
                ])
                ->latest()
                ->paginate(10)
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
            'payment_method' => 'required|string|in:cod,card,easypaisa,jazzcash',
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
    public function show(\App\Models\Order $order, Request $request)
    {
        $user = $request->user();
        $order->load(['items.product', 'shop', 'rider']);
        $order->loadCount([
            'messages' => function ($q) use ($user) {
                $q->where('sender_id', '!=', $user->id)->where('is_read', false);
            }
        ]);

        return new \App\Http\Resources\OrderResource($order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\Order $order)
    {
        $user = $request->user();

        if ($user->role === 'customer') {
            if ($order->customer_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            return response()->json(['message' => 'Customers cannot update order status directly.'], 403);
        }

        // Authorization: Shop owner can update their shop's orders
        if ($user->role === 'shop' && $order->shop_id !== $user->shop?->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Authorization: Rider logic
        if ($user->role === 'rider') {
            // If claiming an unassigned ready order -> change to accepted
            if ($request->status === 'accepted' && $order->status === 'ready' && !$order->rider_id) {
                $order->update(['rider_id' => $user->rider->id]);
            } elseif ($order->rider_id !== $user->rider?->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        if (!in_array($user->role, ['shop', 'rider', 'admin'], true)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:pending,accepted,ready,pickup,delivered',
            'rider_id' => 'nullable|exists:riders,id',
        ]);

        $order->update($validated);

        // Notify Customer of status change
        $statusMessages = [
            'accepted' => "Your order #{$order->id} has been accepted by {$order->shop->name}.",
            'ready' => "Order #{$order->id} is ready for pickup!",
            'pickup' => "Rider {$user->name} is on the way with your order!",
            'delivered' => "Order #{$order->id} has been delivered. Enjoy!",
            'cancelled' => "Order #{$order->id} has been cancelled.",
        ];

        if (isset($statusMessages[$order->status])) {
            Notification::create([
                'user_id' => $order->customer_id,
                'title' => 'Order Update',
                'message' => $statusMessages[$order->status],
                'type' => 'order_status',
                'data' => ['order_id' => $order->id, 'status' => $order->status],
            ]);
        }

        return new \App\Http\Resources\OrderResource($order->load(['items.product', 'shop', 'customer']));
    }

    /**
     * Update rider location for an order
     */
    public function updateLocation(Request $request, \App\Models\Order $order)
    {
        $user = $request->user();

        // Only the assigned rider can update the location
        if ($user->role !== 'rider' || $order->rider_id !== $user->rider?->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $order->update([
            'rider_latitude' => $validated['latitude'],
            'rider_longitude' => $validated['longitude'],
        ]);

        return response()->json([
            'message' => 'Location updated successfully',
            'lat' => $order->rider_latitude,
            'lng' => $order->rider_longitude,
        ]);
    }

    public function cancel(Request $request, \App\Models\Order $order)
    {
        $user = $request->user();
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $isCustomerOwner = $user->role === 'customer' && $order->customer_id === $user->id;
        $isShopOwner = $user->role === 'shop' && $order->shop_id === $user->shop?->id;
        $isAdmin = $user->role === 'admin';

        if (!$isCustomerOwner && !$isShopOwner && !$isAdmin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (in_array($order->status, ['delivered', 'cancelled'], true)) {
            return response()->json(['message' => "Order cannot be cancelled in '{$order->status}' state."], 409);
        }

        if ($isCustomerOwner && !in_array($order->status, ['pending', 'accepted'], true)) {
            return response()->json(['message' => 'Customer can only cancel pending or accepted orders.'], 409);
        }

        DB::beginTransaction();
        try {
            // Restore stock for all items.
            $order->loadMissing('items.product', 'payment');
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                }
            }

            $order->update(['status' => 'cancelled']);

            if ($order->payment) {
                if (
                    $order->payment->method === 'card' &&
                    $order->payment->status === 'completed' &&
                    !empty($order->payment->transaction_id)
                ) {
                    Stripe::setApiKey(config('services.stripe.secret'));
                    Refund::create([
                        'payment_intent' => $order->payment->transaction_id,
                        'reason' => 'requested_by_customer',
                    ]);
                    $order->payment->update([
                        'status' => 'refunded',
                        'metadata' => array_merge($order->payment->metadata ?? [], [
                            'cancel_reason' => $validated['reason'] ?? null,
                            'cancelled_by' => $user->role,
                        ]),
                    ]);
                    $order->update(['payment_status' => 'refunded']);
                } elseif ($order->payment->status === 'pending') {
                    $order->payment->update([
                        'status' => 'failed',
                        'metadata' => array_merge($order->payment->metadata ?? [], [
                            'cancel_reason' => $validated['reason'] ?? null,
                            'cancelled_by' => $user->role,
                        ]),
                    ]);
                    $order->update(['payment_status' => 'failed']);
                }
            }

            Notification::create([
                'user_id' => $order->customer_id,
                'title' => 'Order Cancelled',
                'message' => "Order #{$order->id} was cancelled.",
                'type' => 'order_status',
                'data' => [
                    'order_id' => $order->id,
                    'status' => 'cancelled',
                    'reason' => $validated['reason'] ?? null,
                ],
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Order cancelled successfully',
                'order' => new \App\Http\Resources\OrderResource($order->fresh()->load(['items.product', 'shop', 'customer'])),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to cancel order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function stats(Request $request)
    {
        $user = $request->user();
        if (!$user->shop) {
            return response()->json(['message' => 'Shop not found'], 404);
        }

        $shopId = $user->shop->id;
        $orders = \App\Models\Order::where('shop_id', $shopId);

        // Calculate Revenue and Growth
        $totalRevenue = (float) $orders->where('status', 'delivered')->sum('total_amount');

        $lastWeekRevenue = \App\Models\Order::where('shop_id', $shopId)
            ->where('status', 'delivered')
            ->where('created_at', '>=', now()->subDays(14))
            ->where('created_at', '<', now()->subDays(7))
            ->sum('total_amount');

        $thisWeekRevenue = \App\Models\Order::where('shop_id', $shopId)
            ->where('status', 'delivered')
            ->where('created_at', '>=', now()->subDays(7))
            ->sum('total_amount');

        $growth = 0;
        if ($lastWeekRevenue > 0) {
            $growth = (($thisWeekRevenue - $lastWeekRevenue) / $lastWeekRevenue) * 100;
        }

        // Daily Revenue for Chart (Last 7 Days)
        $dailyRevenue = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayRevenue = \App\Models\Order::where('shop_id', $shopId)
                ->where('status', 'delivered')
                ->whereDate('created_at', $date)
                ->sum('total_amount');

            $dailyRevenue[] = [
                'date' => $date,
                'revenue' => (float) $dayRevenue,
                'label' => now()->subDays($i)->format('D')
            ];
        }

        // Recent Activity (Last 10 Orders)
        $recentActivity = \App\Models\Order::where('shop_id', $shopId)
            ->with('customer')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'type' => 'new_order',
                    'title' => "New order received #{$order->id}",
                    'message' => "Order from " . ($order->customer?->name ?? 'Guest'),
                    'amount' => $order->total_amount,
                    'status' => $order->status,
                    'time' => $order->created_at->diffForHumans()
                ];
            });

        return response()->json([
            'total_revenue' => $totalRevenue,
            'total_orders' => $orders->count(),
            'pending_orders' => \App\Models\Order::where('shop_id', $shopId)->where('status', 'pending')->count(),
            'delivered_orders' => \App\Models\Order::where('shop_id', $shopId)->where('status', 'delivered')->count(),
            'growth_percentage' => round($growth, 1),
            'daily_revenue' => $dailyRevenue,
            'recent_activity' => $recentActivity
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
