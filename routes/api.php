<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\FavoriteController;


// Public Routes
Route::get("test", function () {
    return response()->json([
        "message" => "Hello World",
    ]);
});

Route::middleware('throttle:api')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/products/search', [ProductController::class, 'search']);
});

Route::get('/shops', [ShopController::class, 'index']);
Route::get('/shops/categories', [ShopController::class, 'categories']);
Route::get('/shops/{shop}', [ShopController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/suggestions', [ProductController::class, 'suggestions']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/products/{product}/reviews', [ReviewController::class, 'index']);
Route::post('/payments/webhook', [PaymentController::class, 'webhook']);

// New endpoints for homepage
Route::get('/products/featured/list', [ProductController::class, 'featured']);
Route::get('/products/flash-sale/list', [ProductController::class, 'flashSale']);
Route::get('/shops/top-rated/list', [ShopController::class, 'topRated']);


// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Shop Routes (Protected)
    Route::post('/shops', [ShopController::class, 'store']);
    Route::put('/shops/{shop}', [ShopController::class, 'update']);
    Route::delete('/shops/{shop}', [ShopController::class, 'destroy']);

    // Product Routes (Protected)
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // Order Routes
    Route::get('/orders/stats', [OrderController::class, 'stats']);
    Route::patch('/orders/{order}/location', [OrderController::class, 'updateLocation']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::apiResource('orders', OrderController::class);

    // Payment Routes
    Route::post('/payments/intent', [PaymentController::class, 'createPaymentIntent']);
    Route::post('/payments/{payment}/confirm', [PaymentController::class, 'confirmPayment']);
    Route::get('/orders/{order}/payment', [PaymentController::class, 'show']);

    // Address Routes
    Route::apiResource('addresses', AddressController::class);

    // Review Routes
    Route::post('/reviews', [ReviewController::class, 'store']);

    // Notification Routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    // New route for mass marking as read
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Chat Routes
    Route::get('/orders/{order}/messages', [ChatController::class, 'index']);
    Route::post('/orders/{order}/messages', [ChatController::class, 'store']);

    // Favorites
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'toggle']);

    // Profile Update
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);

    // Admin Routes
    Route::prefix('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);
        Route::get('/shops', [AdminController::class, 'shops']);
        Route::get('/riders', [AdminController::class, 'riders']);
        Route::get('/users', [AdminController::class, 'users']);

        // Moderation
        Route::put('/shops/{shop}/toggle-status', [AdminController::class, 'toggleShopStatus']);
        Route::put('/riders/{rider}/toggle-status', [AdminController::class, 'toggleRiderStatus']);
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);
    });
});
