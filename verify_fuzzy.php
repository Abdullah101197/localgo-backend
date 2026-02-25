<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

$controller = new App\Http\Controllers\Api\ProductController();

// 1. Test "pandol" (typo) -> expecting "Panadol Extra"
echo "\n--- Test 1: Search 'pandol' (typo) ---\n";
$request = Illuminate\Http\Request::create('/api/products/search', 'GET', ['query' => 'pandol']);
$response = $controller->search($request);
$data = $response->resource->items();
echo "Found " . count($data) . " items.\n";
foreach ($data as $item) {
    echo "- " . $item->name . "\n";
}

// 2. Test Suggestions for "pand" (typo)
echo "\n--- Test 2: Suggestions 'pand' ---\n";
$request = Illuminate\Http\Request::create('/api/products/suggestions', 'GET', ['query' => 'pand']);
$response = $controller->suggestions($request);
echo "Suggestions: " . json_encode($response->getData()) . "\n";

// 3. Test "Panadol" (exact) to ensure no regression
echo "\n--- Test 3: Search 'Panadol' (exact) ---\n";
$request = Illuminate\Http\Request::create('/api/products/search', 'GET', ['query' => 'Panadol']);
$response = $controller->search($request);
$data = $response->resource->items();
echo "Found " . count($data) . " items.\n";
