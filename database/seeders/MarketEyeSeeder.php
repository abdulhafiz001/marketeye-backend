<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Market;
use App\Models\PriceSnapshot;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MarketEyeSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@marketeye.ng'],
            [
                'name' => 'Market Eye Admin',
                'password' => Hash::make('admin123'),
                'phone' => '08000000000',
                'role' => User::ROLE_ADMIN,
                'points' => 0,
                'verified' => true,
            ]
        );

        $markets = [
            ['name' => 'Wuse Market', 'area' => 'Wuse', 'latitude' => 9.0765, 'longitude' => 7.3986],
            ['name' => 'Utako Market', 'area' => 'Utako', 'latitude' => 9.0579, 'longitude' => 7.4951],
            ['name' => 'Kado Market', 'area' => 'Kado', 'latitude' => 9.0820, 'longitude' => 7.3983],
            ['name' => 'Zuba Market', 'area' => 'Zuba', 'latitude' => 9.1756, 'longitude' => 7.3200],
            ['name' => 'Gwagwalada Market', 'area' => 'Gwagwalada', 'latitude' => 8.9434, 'longitude' => 7.0816],
            ['name' => 'Nyanya Market', 'area' => 'Nyanya', 'latitude' => 9.0279, 'longitude' => 7.5636],
            ['name' => 'Lugbe Market', 'area' => 'Lugbe', 'latitude' => 8.9902, 'longitude' => 7.3370],
            ['name' => 'Garki Market', 'area' => 'Garki', 'latitude' => 9.0412, 'longitude' => 7.4898],
            ['name' => 'Maitama Market', 'area' => 'Maitama', 'latitude' => 9.0880, 'longitude' => 7.4933],
            ['name' => 'Kubwa Market', 'area' => 'Kubwa', 'latitude' => 9.1165, 'longitude' => 7.3388],
        ];

        $marketModels = collect($markets)->map(function (array $m) {
            return Market::query()->firstOrCreate(
                ['name' => $m['name']],
                [
                    'area' => $m['area'],
                    'city' => 'Abuja',
                    'state' => 'FCT',
                    'latitude' => $m['latitude'],
                    'longitude' => $m['longitude'],
                    'description' => 'Major Abuja market.',
                    'is_active' => true,
                ]
            );
        });

        $categoryDefs = [
            ['name' => 'Vegetables', 'slug' => 'vegetables', 'icon' => 'carrot'],
            ['name' => 'Grains & Cereals', 'slug' => 'grains-cereals', 'icon' => 'barley'],
            ['name' => 'Protein & Meat', 'slug' => 'protein-meat', 'icon' => 'food-drumstick'],
            ['name' => 'Cooking Essentials', 'slug' => 'cooking-essentials', 'icon' => 'pot-steam'],
            ['name' => 'Fruits', 'slug' => 'fruits', 'icon' => 'fruit-grapes'],
            ['name' => 'Beverages', 'slug' => 'beverages', 'icon' => 'cup'],
        ];

        $cats = collect($categoryDefs)->map(fn ($c) => Category::query()->firstOrCreate(
            ['slug' => $c['slug']],
            ['name' => $c['name'], 'icon' => $c['icon']]
        ));

        $vegetables = $cats->firstWhere('slug', 'vegetables')->id;
        $grains = $cats->firstWhere('slug', 'grains-cereals')->id;
        $protein = $cats->firstWhere('slug', 'protein-meat')->id;
        $cooking = $cats->firstWhere('slug', 'cooking-essentials')->id;
        $fruits = $cats->firstWhere('slug', 'fruits')->id;
        $beverages = $cats->firstWhere('slug', 'beverages')->id;

        $products = [
            ['category_id' => $vegetables, 'name' => 'Tomatoes', 'unit' => 'per basket'],
            ['category_id' => $vegetables, 'name' => 'Onions', 'unit' => 'per kg'],
            ['category_id' => $vegetables, 'name' => 'Fresh Pepper', 'unit' => 'per kg'],
            ['category_id' => $vegetables, 'name' => 'Cabbage', 'unit' => 'per head'],
            ['category_id' => $vegetables, 'name' => 'Carrots', 'unit' => 'per kg'],
            ['category_id' => $vegetables, 'name' => 'Green Beans', 'unit' => 'per kg'],
            ['category_id' => $vegetables, 'name' => 'Spinach', 'unit' => 'per bundle'],
            ['category_id' => $grains, 'name' => 'Rice (Imported)', 'unit' => '50kg bag'],
            ['category_id' => $grains, 'name' => 'Rice (Local)', 'unit' => '50kg bag'],
            ['category_id' => $grains, 'name' => 'Maize', 'unit' => 'per kg'],
            ['category_id' => $grains, 'name' => 'Millet', 'unit' => 'per kg'],
            ['category_id' => $grains, 'name' => 'Garri (White)', 'unit' => 'per kg'],
            ['category_id' => $grains, 'name' => 'Garri (Yellow)', 'unit' => 'per kg'],
            ['category_id' => $grains, 'name' => 'Semolina', 'unit' => '10kg bag'],
            ['category_id' => $protein, 'name' => 'Beef', 'unit' => 'per kg'],
            ['category_id' => $protein, 'name' => 'Goat Meat', 'unit' => 'per kg'],
            ['category_id' => $protein, 'name' => 'Chicken (Broiler)', 'unit' => 'per kg'],
            ['category_id' => $protein, 'name' => 'Titus Fish', 'unit' => 'per kg'],
            ['category_id' => $protein, 'name' => 'Eggs', 'unit' => 'per crate'],
            ['category_id' => $cooking, 'name' => 'Palm Oil', 'unit' => 'per litre'],
            ['category_id' => $cooking, 'name' => 'Vegetable Oil', 'unit' => '5L bottle'],
            ['category_id' => $cooking, 'name' => 'Salt', 'unit' => 'per bag'],
            ['category_id' => $cooking, 'name' => 'Maggi Cubes', 'unit' => 'per pack'],
            ['category_id' => $cooking, 'name' => 'Sugar', 'unit' => 'per kg'],
            ['category_id' => $cooking, 'name' => 'Flour', 'unit' => '50kg bag'],
            ['category_id' => $fruits, 'name' => 'Plantain', 'unit' => 'per bunch'],
            ['category_id' => $fruits, 'name' => 'Oranges', 'unit' => 'per kg'],
            ['category_id' => $fruits, 'name' => 'Watermelon', 'unit' => 'per piece'],
            ['category_id' => $fruits, 'name' => 'Pineapple', 'unit' => 'per piece'],
            ['category_id' => $fruits, 'name' => 'Banana', 'unit' => 'per bunch'],
            ['category_id' => $fruits, 'name' => 'Mango', 'unit' => 'per kg'],
            ['category_id' => $beverages, 'name' => 'Pure Water (bag)', 'unit' => 'per bag'],
            ['category_id' => $beverages, 'name' => 'Bottled Water', 'unit' => '75cl'],
            ['category_id' => $beverages, 'name' => 'Soft Drink', 'unit' => '50cl bottle'],
            ['category_id' => $cooking, 'name' => 'Beans (Oloyin)', 'unit' => 'per kg'],
            ['category_id' => $cooking, 'name' => 'Beans (Brown)', 'unit' => 'per kg'],
            ['category_id' => $vegetables, 'name' => 'Irish Potatoes', 'unit' => 'per kg'],
            ['category_id' => $vegetables, 'name' => 'Sweet Potatoes', 'unit' => 'per kg'],
            ['category_id' => $grains, 'name' => 'Wheat', 'unit' => 'per kg'],
            ['category_id' => $protein, 'name' => 'Turkey', 'unit' => 'per kg'],
            ['category_id' => $protein, 'name' => 'Smoked Fish', 'unit' => 'per kg'],
            ['category_id' => $cooking, 'name' => 'Yam', 'unit' => 'per tuber'],
        ];

        $productModels = collect($products)->map(function (array $row) {
            $product = Product::query()->create([
                'category_id' => $row['category_id'],
                'name' => $row['name'],
                'unit' => $row['unit'],
                'slug' => 'tmp-'.Str::lower(Str::random(10)),
                'description' => null,
                'is_active' => true,
            ]);
            $product->update(['slug' => Str::slug($row['name']).'-'.$product->id]);

            return $product->fresh();
        });

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        foreach ($marketModels as $mi => $market) {
            foreach ($productModels as $pi => $product) {
                $base = 400 + (($pi * 37 + $mi * 19) % 9000);
                $spread = max(50, (int) ($base * 0.04));
                $min = $base - $spread;
                $max = $base + $spread;
                $avg = ($min + $max) / 2;
                $count = 2 + ($pi % 4);

                PriceSnapshot::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'market_id' => $market->id,
                        'snapshot_date' => $today,
                    ],
                    [
                        'avg_price' => round($avg, 2),
                        'min_price' => round($min, 2),
                        'max_price' => round($max, 2),
                        'submission_count' => $count,
                        'low_confidence' => $count < 2,
                        'snapshot_source' => PriceSnapshot::SOURCE_SUBMISSION_AGGREGATE,
                    ]
                );

                $oldBase = $base * (0.92 + (($pi + $mi) % 5) * 0.02);
                $oldSpread = max(40, (int) ($oldBase * 0.04));
                PriceSnapshot::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'market_id' => $market->id,
                        'snapshot_date' => $yesterday,
                    ],
                    [
                        'avg_price' => round($oldBase, 2),
                        'min_price' => round($oldBase - $oldSpread, 2),
                        'max_price' => round($oldBase + $oldSpread, 2),
                        'submission_count' => max(2, $count - 1),
                        'low_confidence' => false,
                        'snapshot_source' => PriceSnapshot::SOURCE_SUBMISSION_AGGREGATE,
                    ]
                );
            }
        }
    }
}
