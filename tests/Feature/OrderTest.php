<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shop;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected $shopOwner;
    protected $shop;
    protected $product;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a shop owner and a shop
        $this->shopOwner = User::factory()->create(['role' => 'shop']);
        $this->shop = Shop::factory()->create(['user_id' => $this->shopOwner->id]);

        // Create a product
        $this->product = Product::factory()->create([
            'shop_id' => $this->shop->id,
            'price' => 100
        ]);

        // Create a customer
        $this->customer = User::factory()->create(['role' => 'customer']);
    }

    public function test_customer_can_place_order()
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/orders', [
                'shop_id' => $this->shop->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                        'price' => 100
                    ]
                ],
                'total_amount' => 200,
                'delivery_address' => '123 Test St',
                'delivery_latitude' => 10.0,
                'delivery_longitude' => 20.0,
                'payment_method' => 'cod'
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer->id,
            'shop_id' => $this->shop->id,
            'status' => 'pending'
        ]);
    }

    public function test_shop_owner_can_update_order_status()
    {
        $order = \App\Models\Order::factory()->create([
            'customer_id' => $this->customer->id,
            'shop_id' => $this->shop->id,
            'status' => 'pending',
            'total_amount' => 100
        ]);

        $response = $this->actingAs($this->shopOwner, 'sanctum')
            ->putJson("/api/orders/{$order->id}", [
                'status' => 'accepted'
            ]);

        $response->assertStatus(200);
        $this->assertEquals('accepted', $order->fresh()->status);
    }
}
