<?php

namespace Database\Seeders;

use App\Models\Market;
use App\Models\PriceSnapshot;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Adds five Abuja-area markets and conservative reference prices for products
 * whose catalog units can be mapped to published North Central price data.
 * Run independently with: php artisan db:seed --class=AbujaExpansionMarketsSeeder
 */
class AbujaExpansionMarketsSeeder extends Seeder
{
    /**
     * May 2026 NBS North Central unit prices, as displayed in the Abuja.Cool
     * compilation of NBS Selected Food Price Watch. Values use catalog units;
     * products without a defensible unit match are deliberately not guessed.
     */
    private const REFERENCE_PRICES = [
        'Tomatoes' => 1451,
        'Onions' => 931,
        'Fresh Pepper' => 2638, // NBS Rodo pepper, 1kg
        'Carrots' => 1751,
        'Rice (Imported)' => 136050, // NBS Abuja state high, ₦2,721/kg, scaled to 50kg
        'Rice (Local)' => 114450, // Local broken rice, ₦2,289/kg, scaled to 50kg
        'Maize' => 704,
        'Garri (White)' => 676,
        'Garri (Yellow)' => 813,
        'Beef' => 6999,
        'Goat Meat' => 5722,
        'Chicken (Broiler)' => 6309,
        'Titus Fish' => 8112,
        'Eggs' => 5990,
        'Palm Oil' => 3276, // ₦2,457 per 75cl, normalized to 1 litre
        'Vegetable Oil' => 21007, // ₦3,151 per 75cl, normalized to 5 litres
        'Beans (Brown)' => 1249,
        'Irish Potatoes' => 1762,
        'Sweet Potatoes' => 695,
        'Smoked Fish' => 6334,
        'Yam' => 2120,
        'Watermelon' => 1920,
    ];

    private const MARKETS = [
        ['name' => 'Mararaba Market', 'area' => 'Mararaba', 'city' => 'Mararaba', 'state' => 'Nasarawa', 'latitude' => 9.0355, 'longitude' => 7.5730, 'factor' => 0.95],
        ['name' => 'Wuse Market', 'area' => 'Wuse', 'city' => 'Abuja', 'state' => 'FCT', 'latitude' => 9.0765, 'longitude' => 7.3986, 'factor' => 1.05],
        ['name' => 'Nyanya Market', 'area' => 'Nyanya', 'city' => 'Abuja', 'state' => 'FCT', 'latitude' => 9.0279, 'longitude' => 7.5636, 'factor' => 0.94],
        ['name' => 'Karu Market', 'area' => 'Karu', 'city' => 'Abuja', 'state' => 'FCT', 'latitude' => 9.0060, 'longitude' => 7.5720, 'factor' => 0.96],
        ['name' => 'Area 1 Market', 'area' => 'Garki Area 1', 'city' => 'Abuja', 'state' => 'FCT', 'latitude' => 9.0348, 'longitude' => 7.4840, 'factor' => 1.03],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $today = now()->toDateString();
            $products = Product::query()->where('is_active', true)->get();

            foreach (self::MARKETS as $definition) {
                $market = Market::query()->firstOrCreate(
                    ['name' => $definition['name']],
                    [
                        'area' => $definition['area'],
                        'city' => $definition['city'],
                        'state' => $definition['state'],
                        'latitude' => $definition['latitude'],
                        'longitude' => $definition['longitude'],
                        'description' => 'Major market serving '.$definition['area'].'.',
                        'is_active' => true,
                    ]
                );

                foreach ($products as $product) {
                    $base = self::REFERENCE_PRICES[$product->name] ?? null;
                    if ($base === null) {
                        continue;
                    }

                    $price = (int) (round(($base * $definition['factor']) / 50) * 50);
                    $spread = max(50, (int) (round(($price * 0.04) / 50) * 50));

                    PriceSnapshot::query()->firstOrCreate(
                        [
                            'product_id' => $product->id,
                            'market_id' => $market->id,
                            'snapshot_date' => $today,
                        ],
                        [
                            'avg_price' => $price,
                            'min_price' => max(50, $price - $spread),
                            'max_price' => $price + $spread,
                            'submission_count' => 0,
                            'low_confidence' => true,
                            'snapshot_source' => PriceSnapshot::SOURCE_EXTERNAL_SEED,
                        ]
                    );
                }
            }
        });
    }
}
