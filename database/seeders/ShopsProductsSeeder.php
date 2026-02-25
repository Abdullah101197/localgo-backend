<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shop;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ShopsProductsSeeder extends Seeder
{
    public function run()
    {
        // Create merchant users for shops
        $merchants = [];
        for ($i = 1; $i <= 10; $i++) {
            $merchants[] = User::create([
                'name' => "Merchant $i",
                'email' => "merchant$i@example.com",
                'password' => Hash::make('password'),
                'role' => 'merchant',
                'phone' => '03' . str_pad($i, 9, '0', STR_PAD_LEFT),
            ]);
        }

        // Shop data with categories
        $shopsData = [
            // Groceries (2 shops)
            [
                'name' => 'Fresh Mart Groceries',
                'category' => 'Groceries',
                'address' => 'Block A, Gulberg, Lahore',
                'latitude' => 31.5204 + (rand(-100, 100) / 10000),
                'longitude' => 74.3587 + (rand(-100, 100) / 10000),
                'image_url' => 'https://images.unsplash.com/photo-1604719312566-8912e9227c6a?auto=format&fit=crop&q=80&w=600',
            ],
            [
                'name' => 'Green Valley Organic Store',
                'category' => 'Groceries',
                'address' => 'DHA Phase 5, Lahore',
                'latitude' => 31.4697 + (rand(-100, 100) / 10000),
                'longitude' => 74.4084 + (rand(-100, 100) / 10000),
                'image_url' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&q=80&w=600',
            ],

            // Food (2 shops)
            [
                'name' => 'Spice Kitchen Restaurant',
                'category' => 'Food',
                'address' => 'MM Alam Road, Lahore',
                'latitude' => 31.5081 + (rand(-100, 100) / 10000),
                'longitude' => 74.3534 + (rand(-100, 100) / 10000),
                'image_url' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&q=80&w=600',
            ],
            [
                'name' => 'Burger House Fast Food',
                'category' => 'Food',
                'address' => 'Johar Town, Lahore',
                'latitude' => 31.4697 + (rand(-100, 100) / 10000),
                'longitude' => 74.2728 + (rand(-100, 100) / 10000),
                'image_url' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&q=80&w=600',
            ],

            // Fashion (2 shops)
            [
                'name' => 'Style Hub Fashion',
                'category' => 'Fashion',
                'address' => 'Liberty Market, Lahore',
                'latitude' => 31.5204 + (rand(-100, 100) / 10000),
                'longitude' => 74.3587 + (rand(-100, 100) / 10000),
                'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&q=80&w=600',
            ],
            [
                'name' => 'Trendy Threads Boutique',
                'category' => 'Fashion',
                'address' => 'Packages Mall, Lahore',
                'latitude' => 31.4697 + (rand(-100, 100) / 10000),
                'longitude' => 74.3943 + (rand(-100, 100) / 10000),
                'image_url' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?auto=format&fit=crop&q=80&w=600',
            ],

            // Electronics (2 shops)
            [
                'name' => 'Tech Zone Electronics',
                'category' => 'Electronics',
                'address' => 'Hafeez Center, Lahore',
                'latitude' => 31.5204 + (rand(-100, 100) / 10000),
                'longitude' => 74.3587 + (rand(-100, 100) / 10000),
                'image_url' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&q=80&w=600',
            ],
            [
                'name' => 'Digital World Store',
                'category' => 'Electronics',
                'address' => 'Fortress Stadium, Lahore',
                'latitude' => 31.5081 + (rand(-100, 100) / 10000),
                'longitude' => 74.3534 + (rand(-100, 100) / 10000),
                'image_url' => 'https://images.unsplash.com/photo-1491933382434-500287f9b54b?auto=format&fit=crop&q=80&w=600',
            ],

            // Home (1 shop)
            [
                'name' => 'Home Decor Paradise',
                'category' => 'Home',
                'address' => 'Emporium Mall, Lahore',
                'latitude' => 31.4697 + (rand(-100, 100) / 10000),
                'longitude' => 74.3943 + (rand(-100, 100) / 10000),
                'image_url' => 'https://images.unsplash.com/photo-1556912173-46c336c7fd55?auto=format&fit=crop&q=80&w=600',
            ],

            // Beauty (1 shop)
            [
                'name' => 'Glamour Beauty Salon',
                'category' => 'Beauty',
                'address' => 'Gulberg Main Boulevard, Lahore',
                'latitude' => 31.5204 + (rand(-100, 100) / 10000),
                'longitude' => 74.3587 + (rand(-100, 100) / 10000),
                'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&q=80&w=600',
            ],
        ];

        // Create shops
        $shops = [];
        foreach ($shopsData as $index => $shopData) {
            $shops[] = Shop::create(array_merge($shopData, [
                'user_id' => $merchants[$index]->id,
                'is_verified' => rand(0, 1),
                'is_active' => 1,
            ]));
        }

        // Product data by category
        $productsData = [
            // Groceries products (20 products)
            'Groceries' => [
                ['name' => 'Fresh Apples (1kg)', 'price' => 250, 'description' => 'Fresh red apples from Kashmir', 'image' => 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Organic Bananas (1 dozen)', 'price' => 120, 'description' => 'Organic ripe bananas', 'image' => 'https://images.unsplash.com/photo-1603833665858-e61d17a86224?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Fresh Milk (1L)', 'price' => 180, 'description' => 'Farm fresh milk', 'image' => 'https://images.unsplash.com/photo-1563636619-e9143da7973b?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Brown Eggs (12 pcs)', 'price' => 220, 'description' => 'Farm fresh brown eggs', 'image' => 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Basmati Rice (5kg)', 'price' => 850, 'description' => 'Premium basmati rice', 'image' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Whole Wheat Flour (5kg)', 'price' => 450, 'description' => 'Stone ground wheat flour', 'image' => 'https://images.unsplash.com/photo-1574323347407-f5e1ad6d020b?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Fresh Tomatoes (1kg)', 'price' => 80, 'description' => 'Locally grown tomatoes', 'image' => 'https://images.unsplash.com/photo-1546094096-0df4bcaaa337?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Green Chilies (250g)', 'price' => 50, 'description' => 'Fresh green chilies', 'image' => 'https://images.unsplash.com/photo-1583663848850-46af132dc08e?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Cooking Oil (1L)', 'price' => 380, 'description' => 'Pure cooking oil', 'image' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Sugar (1kg)', 'price' => 120, 'description' => 'White refined sugar', 'image' => 'https://images.unsplash.com/photo-1587735243615-c03f25aaff15?auto=format&fit=crop&q=80&w=400'],
            ],

            // Food products (20 products)
            'Food' => [
                ['name' => 'Chicken Biryani', 'price' => 350, 'description' => 'Delicious chicken biryani with raita', 'image' => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Beef Burger', 'price' => 280, 'description' => 'Juicy beef burger with fries', 'image' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Chicken Pizza (Large)', 'price' => 950, 'description' => 'Large chicken pizza with extra cheese', 'image' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Chicken Karahi', 'price' => 1200, 'description' => 'Spicy chicken karahi (full)', 'image' => 'https://images.unsplash.com/photo-1603894584373-5ac82b2ae398?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Zinger Burger', 'price' => 320, 'description' => 'Crispy zinger burger', 'image' => 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Chicken Shawarma', 'price' => 250, 'description' => 'Chicken shawarma wrap', 'image' => 'https://images.unsplash.com/photo-1529006557810-274b9b2fc783?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Beef Nihari', 'price' => 450, 'description' => 'Traditional beef nihari', 'image' => 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Chicken Wings (6 pcs)', 'price' => 380, 'description' => 'Spicy chicken wings', 'image' => 'https://images.unsplash.com/photo-1608039829572-78524f79c4c7?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Pasta Alfredo', 'price' => 550, 'description' => 'Creamy pasta alfredo', 'image' => 'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Club Sandwich', 'price' => 420, 'description' => 'Triple decker club sandwich', 'image' => 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&q=80&w=400'],
            ],

            // Fashion products (20 products)
            'Fashion' => [
                ['name' => 'Men Cotton Shirt', 'price' => 1500, 'description' => 'Premium cotton formal shirt', 'image' => 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Women Lawn Suit', 'price' => 3500, 'description' => 'Embroidered lawn 3-piece suit', 'image' => 'https://images.unsplash.com/photo-1583391733956-6c78276477e2?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Men Jeans', 'price' => 2200, 'description' => 'Slim fit denim jeans', 'image' => 'https://images.unsplash.com/photo-1542272604-787c3835535d?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Women Handbag', 'price' => 2800, 'description' => 'Leather handbag', 'image' => 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Men Sneakers', 'price' => 3500, 'description' => 'Comfortable sports sneakers', 'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Women Heels', 'price' => 2500, 'description' => 'Elegant high heels', 'image' => 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Men T-Shirt', 'price' => 800, 'description' => 'Cotton casual t-shirt', 'image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Women Dress', 'price' => 4500, 'description' => 'Designer party dress', 'image' => 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Men Watch', 'price' => 5500, 'description' => 'Luxury wrist watch', 'image' => 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Women Sunglasses', 'price' => 1800, 'description' => 'UV protection sunglasses', 'image' => 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&q=80&w=400'],
            ],

            // Electronics products (20 products)
            'Electronics' => [
                ['name' => 'Wireless Earbuds', 'price' => 3500, 'description' => 'Bluetooth wireless earbuds', 'image' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Smartphone', 'price' => 45000, 'description' => '128GB smartphone with 48MP camera', 'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Laptop', 'price' => 85000, 'description' => 'Core i5 8GB RAM laptop', 'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Wireless Mouse', 'price' => 1200, 'description' => 'Ergonomic wireless mouse', 'image' => 'https://images.unsplash.com/photo-1527814050087-3793815479db?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Mechanical Keyboard', 'price' => 4500, 'description' => 'RGB mechanical keyboard', 'image' => 'https://images.unsplash.com/photo-1595225476474-87563907a212?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Portable Speaker', 'price' => 2800, 'description' => 'Bluetooth portable speaker', 'image' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Power Bank 20000mAh', 'price' => 2500, 'description' => 'Fast charging power bank', 'image' => 'https://images.unsplash.com/photo-1609091839311-d5365f9ff1c5?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'USB-C Cable', 'price' => 500, 'description' => 'Fast charging USB-C cable', 'image' => 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Webcam HD', 'price' => 3500, 'description' => '1080p HD webcam', 'image' => 'https://images.unsplash.com/photo-1587825140708-dfaf72ae4b04?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Gaming Headset', 'price' => 5500, 'description' => '7.1 surround sound gaming headset', 'image' => 'https://images.unsplash.com/photo-1599669454699-248893623440?auto=format&fit=crop&q=80&w=400'],
            ],

            // Home products (10 products)
            'Home' => [
                ['name' => 'Bed Sheet Set', 'price' => 2500, 'description' => 'Cotton bed sheet set (king size)', 'image' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Curtains', 'price' => 3500, 'description' => 'Blackout curtains (pair)', 'image' => 'https://images.unsplash.com/photo-1585128903994-2c2a8e3e8a8f?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Wall Clock', 'price' => 1200, 'description' => 'Modern wall clock', 'image' => 'https://images.unsplash.com/photo-1563861826100-9cb868fdbe1c?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Table Lamp', 'price' => 1800, 'description' => 'LED table lamp', 'image' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Cushion Covers (4 pcs)', 'price' => 1500, 'description' => 'Decorative cushion covers', 'image' => 'https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Carpet (5x7 ft)', 'price' => 8500, 'description' => 'Persian design carpet', 'image' => 'https://images.unsplash.com/photo-1600166898405-da9535204843?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Mirror', 'price' => 2200, 'description' => 'Decorative wall mirror', 'image' => 'https://images.unsplash.com/photo-1618220179428-22790b461013?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Vase', 'price' => 1500, 'description' => 'Ceramic flower vase', 'image' => 'https://images.unsplash.com/photo-1578500494198-246f612d3b3d?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Photo Frames (3 pcs)', 'price' => 1200, 'description' => 'Wooden photo frames set', 'image' => 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Storage Basket', 'price' => 800, 'description' => 'Woven storage basket', 'image' => 'https://images.unsplash.com/photo-1610557892470-55d9e80c0bce?auto=format&fit=crop&q=80&w=400'],
            ],

            // Beauty products (10 products)
            'Beauty' => [
                ['name' => 'Face Cream', 'price' => 1500, 'description' => 'Anti-aging face cream', 'image' => 'https://images.unsplash.com/photo-1556228578-0d85b1a4d571?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Lipstick Set', 'price' => 2200, 'description' => 'Matte lipstick set (5 shades)', 'image' => 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Hair Serum', 'price' => 1800, 'description' => 'Nourishing hair serum', 'image' => 'https://images.unsplash.com/photo-1608248543803-ba4f8c70ae0b?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Perfume', 'price' => 3500, 'description' => 'Long-lasting perfume (100ml)', 'image' => 'https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Face Mask', 'price' => 800, 'description' => 'Hydrating face mask', 'image' => 'https://images.unsplash.com/photo-1608248543803-ba4f8c70ae0b?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Nail Polish Set', 'price' => 1200, 'description' => 'Nail polish set (6 colors)', 'image' => 'https://images.unsplash.com/photo-1610992015732-2449b76344bc?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Makeup Brush Set', 'price' => 2500, 'description' => 'Professional makeup brushes', 'image' => 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Shampoo & Conditioner', 'price' => 1500, 'description' => 'Hair care combo pack', 'image' => 'https://images.unsplash.com/photo-1535585209827-a15fcdbc4c2d?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Body Lotion', 'price' => 1200, 'description' => 'Moisturizing body lotion', 'image' => 'https://images.unsplash.com/photo-1556228852-80c3b5d2c7f8?auto=format&fit=crop&q=80&w=400'],
                ['name' => 'Sunscreen SPF 50', 'price' => 1800, 'description' => 'Broad spectrum sunscreen', 'image' => 'https://images.unsplash.com/photo-1556228720-195a672e8a03?auto=format&fit=crop&q=80&w=400'],
            ],
        ];

        // Create products for each shop
        $productCount = 0;
        foreach ($shops as $shop) {
            $categoryProducts = $productsData[$shop->category] ?? [];

            // Create 10 products per shop
            foreach ($categoryProducts as $productData) {
                if ($productCount >= 100)
                    break 2; // Stop at 100 products

                Product::create([
                    'shop_id' => $shop->id,
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'price' => $productData['price'],
                    'category' => $shop->category,
                    'image_url' => $productData['image'],
                    'is_active' => 1,
                    'stock' => rand(10, 100),
                ]);

                $productCount++;
            }
        }

        $this->command->info("✅ Created 10 shops and $productCount products successfully!");
    }
}
