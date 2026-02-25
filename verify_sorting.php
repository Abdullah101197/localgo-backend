<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

// 1. Setup Test Data
try {
    $user = App\Models\User::first();
    if (!$user) {
        echo "Creating dummy user...\n";
        $user = App\Models\User::withoutEvents(function () {
            return App\Models\User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'role' => 'customer'
            ]);
        });
    }

    $lat = 33.6844;
    $lng = 73.0479;

    // Shop A: 0.01 deg away (~1.1km) - 1 Star
    $shopA = App\Models\Shop::withoutEvents(function () use ($user, $lat, $lng) {
        return App\Models\Shop::create([
            'user_id' => $user->id,
            'name' => 'Shop A (Near Bad)',
            'category' => 'Groceries',
            'address' => 'Test Addr',
            'latitude' => $lat + 0.01,
            'longitude' => $lng,
            'delivery_radius' => 50,
            'is_active' => true
        ]);
    });

    $prodA = $shopA->products()->create(['name' => 'P1', 'price' => 10, 'stock' => 10, 'category' => 'Test']);

    $orderA = App\Models\Order::withoutEvents(function () use ($user, $shopA) {
        return App\Models\Order::create([
            'customer_id' => $user->id,
            'shop_id' => $shopA->id,
            'total_amount' => 10,
            'status' => 'delivered',
            'delivery_address' => 'Test',
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'rider_id' => null
        ]);
    });

    App\Models\Review::withoutEvents(function () use ($user, $prodA, $orderA) {
        \App\Models\Review::create(['user_id' => $user->id, 'product_id' => $prodA->id, 'rating' => 1, 'comment' => 'Bad', 'order_id' => $orderA->id]);
    });

    // Shop B: 0.01 deg away (~1.1km) - 5 Stars
    $shopB = App\Models\Shop::withoutEvents(function () use ($user, $lat, $lng) {
        return App\Models\Shop::create([
            'user_id' => $user->id,
            'name' => 'Shop B (Near Good)',
            'category' => 'Groceries',
            'address' => 'Test Addr 2',
            'latitude' => $lat + 0.01,
            'longitude' => $lng,
            'delivery_radius' => 50,
            'is_active' => true
        ]);
    });
    $prodB = $shopB->products()->create(['name' => 'P2', 'price' => 10, 'stock' => 10, 'category' => 'Test']);

    $orderB = App\Models\Order::withoutEvents(function () use ($user, $shopB) {
        return App\Models\Order::create([
            'customer_id' => $user->id,
            'shop_id' => $shopB->id,
            'total_amount' => 10,
            'status' => 'delivered',
            'delivery_address' => 'Test',
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'rider_id' => null
        ]);
    });

    App\Models\Review::withoutEvents(function () use ($user, $prodB, $orderB) {
        \App\Models\Review::create(['user_id' => $user->id, 'product_id' => $prodB->id, 'rating' => 5, 'comment' => 'Good', 'order_id' => $orderB->id]);
    });

    // Shop C: 0.1 deg away (~11km) - 5 Stars
    $shopC = App\Models\Shop::withoutEvents(function () use ($user, $lat, $lng) {
        return App\Models\Shop::create([
            'user_id' => $user->id,
            'name' => 'Shop C (Far Good)',
            'category' => 'Groceries',
            'address' => 'Test Addr 3',
            'latitude' => $lat + 0.1,
            'longitude' => $lng,
            'delivery_radius' => 50,
            'is_active' => true
        ]);
    });
    $prodC = $shopC->products()->create(['name' => 'P3', 'price' => 10, 'stock' => 10, 'category' => 'Test']);

    $orderC = App\Models\Order::withoutEvents(function () use ($user, $shopC) {
        return App\Models\Order::create([
            'customer_id' => $user->id,
            'shop_id' => $shopC->id,
            'total_amount' => 10,
            'status' => 'delivered',
            'delivery_address' => 'Test',
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'rider_id' => null
        ]);
    });

    App\Models\Review::withoutEvents(function () use ($user, $prodC, $orderC) {
        \App\Models\Review::create(['user_id' => $user->id, 'product_id' => $prodC->id, 'rating' => 5, 'comment' => 'Good', 'order_id' => $orderC->id]);
    });

    // 2. Test Sorting
    echo "\n--- Testing Shop Sort (Lat: $lat, Lng: $lng) ---\n";
    $request = Illuminate\Http\Request::create('/api/shops', 'GET', ['latitude' => $lat, 'longitude' => $lng]);
    $controller = new App\Http\Controllers\Api\ShopController();
    $response = $controller->index($request);
    $data = $response->resource->items();

    foreach ($data as $shop) {
        if (strpos($shop->name, 'Shop') === 0) { // Filter our test shops
            echo "{$shop->name} | Dist: " . number_format($shop->distance, 2) . "km | Rating: " . $shop->average_rating . "\n";
        }
    }

    // Cleanup
    $shopA->delete();
    $shopB->delete();
    $shopC->delete();

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
