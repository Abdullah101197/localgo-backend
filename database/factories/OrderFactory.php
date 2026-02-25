<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
            'shop_id' => Shop::factory(),
            'total_amount' => fake()->randomFloat(2, 10, 500),
            'status' => 'pending',
            'delivery_address' => fake()->address(),
            'delivery_latitude' => fake()->latitude(),
            'delivery_longitude' => fake()->longitude(),
        ];
    }
}
