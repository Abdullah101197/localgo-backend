<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Shop;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@localgo.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Create a Customer
        User::firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Ali Customer',
                'password' => Hash::make('password'),
                'role' => 'customer',
            ]
        );

        // Create Shops with Owners
        $shopData = [
            [
                'name' => 'Al Rehman Mart',
                'category' => 'Groceries',
                'email' => 'rehman@example.com',
                'description' => 'Your daily grocery partner.',
                'address' => 'G-9 Markaz, Islamabad',
                'lat' => 33.6844,
                'lng' => 73.0479,
                'image' => 'https://images.unsplash.com/photo-1578916171728-46686eac8d58?auto=format&fit=crop&q=80&w=800',
                'products' => [
                    ['name' => 'Fresh Apples', 'price' => 250, 'category' => 'Fruits', 'img' => 'https://images.unsplash.com/photo-1560806887-1e4cd0b6bcd6?auto=format&fit=crop&q=80&w=400'],
                    ['name' => 'Milk Pack 1L', 'price' => 180, 'category' => 'Dairy', 'img' => 'https://images.unsplash.com/photo-1550583724-125581cc2532?auto=format&fit=crop&q=80&w=400'],
                    ['name' => 'Basmati Rice 5kg', 'price' => 1200, 'category' => 'Grains', 'img' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&q=80&w=400'],
                ]
            ],
            [
                'name' => 'City Medical Store',
                'category' => 'Medicine',
                'email' => 'citymed@example.com',
                'description' => 'All kinds of medicines available.',
                'address' => 'H-8, Islamabad',
                'lat' => 33.6491,
                'lng' => 73.0441,
                'image' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&q=80&w=800',
                'products' => [
                    ['name' => 'Panadol 500mg', 'price' => 50, 'category' => 'Fever', 'img' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?auto=format&fit=crop&q=80&w=400'],
                    ['name' => 'Hand Sanitizer', 'price' => 150, 'category' => 'Hygiene', 'img' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&q=80&w=400'],
                ]
            ],
            [
                'name' => 'Gourmet Bakery',
                'category' => 'Bakery',
                'email' => 'gourmet@example.com',
                'description' => 'Freshly baked cakes and pastries.',
                'address' => 'F-10 Markaz, Islamabad',
                'lat' => 33.6923,
                'lng' => 73.0116,
                'image' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&q=80&w=800',
                'products' => [
                    ['name' => 'Chocolate Cake', 'price' => 1500, 'category' => 'Cakes', 'img' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&q=80&w=400'],
                    ['name' => 'Fresh Baguette', 'price' => 200, 'category' => 'Bread', 'img' => 'https://images.unsplash.com/photo-1597079910443-60c43fc46002?auto=format&fit=crop&q=80&w=400'],
                ]
            ],
            [
                'name' => 'The Burger Shack',
                'category' => 'Fast Food',
                'email' => 'burgershack@example.com',
                'description' => 'Best burgers in town.',
                'address' => 'Blue Area, Islamabad',
                'lat' => 33.7077,
                'lng' => 73.0561,
                'image' => 'https://images.unsplash.com/photo-1571091718767-18b5b1457add?auto=format&fit=crop&q=80&w=800',
                'products' => [
                    ['name' => 'Classic Zinger Burger', 'price' => 550, 'category' => 'Burgers', 'img' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&q=80&w=400'],
                    ['name' => 'Cheese Fries', 'price' => 300, 'category' => 'Sides', 'img' => 'https://images.unsplash.com/photo-1585109649139-366815a0d713?auto=format&fit=crop&q=80&w=400'],
                ]
            ],
        ];

        foreach ($shopData as $data) {
            $owner = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'] . ' Owner',
                    'password' => Hash::make('password'),
                    'role' => 'shop',
                ]
            );

            $shop = Shop::updateOrCreate(
                ['user_id' => $owner->id],
                [
                    'name' => $data['name'],
                    'category' => $data['category'],
                    'description' => $data['description'],
                    'address' => $data['address'],
                    'latitude' => $data['lat'],
                    'longitude' => $data['lng'],
                    'delivery_radius' => 5,
                    'image_url' => $data['image'],
                    'is_verified' => true,
                ]
            );

            foreach ($data['products'] as $pData) {
                Product::updateOrCreate(
                    ['shop_id' => $shop->id, 'name' => $pData['name']],
                    [
                        'description' => $pData['name'] . ' fresh and high quality.',
                        'price' => $pData['price'],
                        'stock' => 100,
                        'category' => $pData['category'],
                        'image_url' => $pData['img'],
                        'is_active' => true,
                    ]
                );
            }
        }

        // Create Riders
        $riders = [
            ['name' => 'Ahmed Rider', 'email' => 'ahmed.rider@example.com'],
            ['name' => 'Zubair Rider', 'email' => 'zubair.rider@example.com'],
        ];

        foreach ($riders as $rData) {
            User::firstOrCreate(
                ['email' => $rData['email']],
                [
                    'name' => $rData['name'],
                    'password' => Hash::make('password'),
                    'role' => 'rider',
                ]
            );
        }
    }
}
