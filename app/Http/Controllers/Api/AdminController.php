<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Shop;
use App\Models\Order;
use App\Models\Rider;

class AdminController extends Controller
{
    public function stats()
    {
        return response()->json([
            'total_users' => User::count(),
            'total_shops' => Shop::count(),
            'total_riders' => Rider::count(),
            'total_orders' => Order::count(),
            'total_revenue' => (float) Order::where('status', 'delivered')->sum('total_amount'),
            'recent_orders' => \App\Http\Resources\OrderResource::collection(Order::with(['shop', 'customer'])->latest()->limit(5)->get()),
        ]);
    }

    public function shops()
    {
        return response()->json(Shop::with('user')->paginate(20));
    }

    public function riders()
    {
        return response()->json(Rider::with('user')->paginate(20));
    }

    public function users()
    {
        return response()->json(User::latest()->paginate(20));
    }

    public function toggleShopStatus(Shop $shop)
    {
        $shop->update(['is_active' => !$shop->is_active]);
        return response()->json([
            'success' => true,
            'is_active' => $shop->is_active,
            'message' => 'Shop status updated successfully'
        ]);
    }

    public function toggleRiderStatus(Rider $rider)
    {
        $rider->update(['is_verified' => !$rider->is_verified]);
        return response()->json([
            'success' => true,
            'is_verified' => $rider->is_verified,
            'message' => 'Rider verification status updated successfully'
        ]);
    }

    public function deleteUser(User $user)
    {
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Cannot delete admin users'], 403);
        }

        $user->delete();
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
