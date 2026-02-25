<?php

use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Verifying Profile & Favorites Backend ---\n";

// 1. Setup User and Product
$user = User::first();
if (!$user) {
    echo "Creating test user...\n";
    $user = User::factory()->create();
}
$product = Product::first();
if (!$product) {
    echo "Creating test product...\n";
    $product = Product::factory()->create();
}

Auth::login($user);
echo "Logged in as User ID: " . $user->id . "\n";
echo "Testing with Product ID: " . $product->id . "\n";

// 2. Test Toggle Favorite (Add)
echo "\n[Test 1] Toggle Favorite (Add)...\n";
$controller = new \App\Http\Controllers\Api\FavoriteController();
$request = new \Illuminate\Http\Request();
$request->merge(['product_id' => $product->id]);

$response = $controller->toggle($request);
$data = $response->getData(true);

if ($data['status'] === 'success' && $data['is_favorite'] === true) {
    echo "PASS: Added to favorites.\n";
} else {
    echo "FAIL: Could not add to favorites.\n";
    print_r($data);
}

// 3. Test Index (List)
echo "\n[Test 2] List Favorites...\n";
$response = $controller->index();
$data = $response->getData(true);

$found = false;
foreach ($data['data'] as $fav) {
    if ($fav['id'] == $product->id) {
        $found = true;
        break;
    }
}

if ($found) {
    echo "PASS: Product found in favorites list.\n";
} else {
    echo "FAIL: Product not found in favorites list.\n";
}

// 4. Test Toggle Favorite (Remove)
echo "\n[Test 3] Toggle Favorite (Remove)...\n";
$response = $controller->toggle($request);
$data = $response->getData(true);

if ($data['status'] === 'success' && $data['is_favorite'] === false) {
    echo "PASS: Removed from favorites.\n";
} else {
    echo "FAIL: Could not remove from favorites.\n";
    print_r($data);
}


// 5. Test Profile Update
echo "\n[Test 4] Profile Update...\n";
$authController = new \App\Http\Controllers\Api\AuthController();
$updateRequest = new \Illuminate\Http\Request();
$newName = "Updated Name " . rand(1000, 9999);
$newPhone = "1234567890";
$updateRequest->merge([
    'name' => $newName,
    'phone' => $newPhone
]);
$updateRequest->setUserResolver(function () use ($user) {
    return $user;
});

try {
    $response = $authController->updateProfile($updateRequest);
    $data = $response->getData(true);

    if ($data['user']['name'] === $newName && $data['user']['phone'] === $newPhone) {
        echo "PASS: Profile updated successfully.\n";
    } else {
        echo "FAIL: Profile update mismatch.\n";
        print_r($data);
    }
} catch (\Exception $e) {
    echo "FAIL: Profile update exception: " . $e->getMessage() . "\n";
}

echo "\n--- Verification Complete ---\n";
