<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\PriceSnapshot;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BasketOptimizerController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.1', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $items = $validated['items'];
        $userLat = isset($validated['latitude']) ? (float) $validated['latitude'] : null;
        $userLng = isset($validated['longitude']) ? (float) $validated['longitude'] : null;

        $productIds = collect($items)->pluck('product_id')->unique()->all();
        $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');

        $markets = Market::query()->where('is_active', true)->get();

        // Get latest price snapshots for all requested products across all markets
        $snapshots = PriceSnapshot::query()
            ->whereIn('product_id', $productIds)
            ->where('snapshot_date', '>=', now()->subDays(30))
            ->orderByDesc('snapshot_date')
            ->get()
            ->groupBy(fn ($s) => "{$s->market_id}_{$s->product_id}");

        $marketResults = [];

        foreach ($markets as $market) {
            $totalCost = 0.0;
            $itemsFound = 0;
            $missingItems = [];
            $itemBreakdown = [];

            foreach ($items as $item) {
                $pid = (int) $item['product_id'];
                $qty = (float) $item['quantity'];
                $product = $products->get($pid);

                $key = "{$market->id}_{$pid}";
                $snapshotList = $snapshots->get($key);
                $latestSnapshot = $snapshotList?->first();

                if ($latestSnapshot && (float) $latestSnapshot->avg_price > 0) {
                    $unitPrice = (float) $latestSnapshot->avg_price;
                    $subtotal = round($unitPrice * $qty, 2);
                    $totalCost += $subtotal;
                    $itemsFound++;

                    $itemBreakdown[] = [
                        'product_id' => $pid,
                        'product_name' => $product?->name ?? 'Product',
                        'unit' => $product?->unit ?? 'unit',
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                        'is_available' => true,
                    ];
                } else {
                    $missingItems[] = $product?->name ?? 'Unknown';
                    $itemBreakdown[] = [
                        'product_id' => $pid,
                        'product_name' => $product?->name ?? 'Product',
                        'unit' => $product?->unit ?? 'unit',
                        'quantity' => $qty,
                        'unit_price' => null,
                        'subtotal' => null,
                        'is_available' => false,
                    ];
                }
            }

            // Distance & Transit Cost estimation (₦300 base + ₦150 per km)
            $distanceKm = null;
            $estimatedTransitCost = 0.0;
            if ($userLat !== null && $userLng !== null && $market->latitude && $market->longitude) {
                $distanceKm = round($this->haversineKm($userLat, $userLng, (float) $market->latitude, (float) $market->longitude), 1);
                $estimatedTransitCost = round(300 + ($distanceKm * 150), 0);
            }

            $marketResults[] = [
                'market' => [
                    'id' => $market->id,
                    'name' => $market->name,
                    'area' => $market->area ?? 'Abuja',
                    'latitude' => $market->latitude,
                    'longitude' => $market->longitude,
                ],
                'items_found_count' => $itemsFound,
                'total_items_requested' => count($items),
                'is_complete' => ($itemsFound === count($items)),
                'basket_cost' => round($totalCost, 2),
                'distance_km' => $distanceKm,
                'estimated_transit_cost' => $estimatedTransitCost,
                'total_with_transit' => round($totalCost + $estimatedTransitCost, 2),
                'missing_items' => $missingItems,
                'breakdown' => $itemBreakdown,
            ];
        }

        // Sort markets: complete baskets with lowest total cost first
        usort($marketResults, function ($a, $b) {
            if ($a['is_complete'] !== $b['is_complete']) {
                return $b['is_complete'] ? 1 : -1;
            }
            return ($a['total_with_transit'] <=> $b['total_with_transit']);
        });

        // Compute savings
        $cheapestComplete = collect($marketResults)->firstWhere('is_complete', true);
        $priciestComplete = collect($marketResults)->where('is_complete', true)->sortByDesc('basket_cost')->first();

        $potentialSavings = 0.0;
        if ($cheapestComplete && $priciestComplete && $priciestComplete['basket_cost'] > $cheapestComplete['basket_cost']) {
            $potentialSavings = round($priciestComplete['basket_cost'] - $cheapestComplete['basket_cost'], 2);
        }

        return $this->success([
            'optimal_market' => $marketResults[0] ?? null,
            'potential_savings' => $potentialSavings,
            'markets' => $marketResults,
        ], 'Basket comparison calculated successfully.');
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
