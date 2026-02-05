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
}
