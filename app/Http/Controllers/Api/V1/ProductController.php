<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\PriceSnapshot;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with('category')
            ->orderBy('name')
            ->get()
            ->map(function (Product $p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'unit' => $p->unit,
                    'description' => $p->description,
                    'image' => $p->image,
                    'category' => [
                        'id' => $p->category?->id,
                        'name' => $p->category?->name,
                        'slug' => $p->category?->slug,
                        'icon' => $p->category?->icon,
                    ],
                ];
            });

        return $this->success(['products' => $products]);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::query()
            ->where('is_active', true)
            ->with('category')
            ->find($id);

        if (! $product) {
            return $this->failure('Product not found.', [], 404);
        }

        $latestByMarket = PriceSnapshot::query()
            ->select('market_id', DB::raw('MAX(snapshot_date) as latest_date'))
            ->where('product_id', $product->id)
            ->groupBy('market_id');

        $markets = PriceSnapshot::query()
            ->joinSub($latestByMarket, 'latest', function ($join) {
                $join->on('price_snapshots.market_id', '=', 'latest.market_id')
                    ->on('price_snapshots.snapshot_date', '=', 'latest.latest_date');
            })
            ->where('price_snapshots.product_id', $product->id)
            ->with('market')
            ->orderBy('avg_price')
            ->get()
            ->map(fn (PriceSnapshot $snapshot) => [
                'market' => [
                    'id' => $snapshot->market?->id,
                    'name' => $snapshot->market?->name,
                    'area' => $snapshot->market?->area,
                ],
                'avg_price' => (float) $snapshot->avg_price,
                'min_price' => (float) $snapshot->min_price,
                'max_price' => (float) $snapshot->max_price,
                'snapshot_date' => $snapshot->snapshot_date?->toDateString(),
                'submission_count' => (int) $snapshot->submission_count,
            ])
            ->values();

        $history = PriceSnapshot::query()
            ->where('product_id', $product->id)
            ->where('snapshot_date', '>=', now()->subDays(30)->toDateString())
            ->selectRaw('snapshot_date, AVG(avg_price) as avg_price')
            ->groupBy('snapshot_date')
            ->orderBy('snapshot_date')
            ->get()
            ->map(fn ($row) => [
                'date' => (string) $row->snapshot_date,
                'avg_price' => round((float) $row->avg_price, 2),
            ])
            ->values();

        $averagePrice = $markets->avg('avg_price');

        return $this->success([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'unit' => $product->unit,
                'description' => $product->description,
                'image' => $product->image,
                'category' => [
                    'id' => $product->category?->id,
                    'name' => $product->category?->name,
                    'slug' => $product->category?->slug,
                    'icon' => $product->category?->icon,
                ],
            ],
            'stats' => [
                'average_price' => $averagePrice ? round((float) $averagePrice, 2) : null,
                'cheapest_market' => $markets->first(),
                'market_count' => $markets->count(),
            ],
            'history' => $history,
            'markets' => $markets,
        ]);
    }
}
