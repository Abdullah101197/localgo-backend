<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShopFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'category' => 'General',
            'description' => fake()->sentence(),
            'address' => fake()->address(),
            'delivery_radius' => 50,
            'is_active' => true,
        ];
    }
}
