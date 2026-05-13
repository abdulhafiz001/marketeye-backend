<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\PriceSnapshot;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PriceCompareController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request): JsonResponse
    {
        $productId = (int) $request->query('product_id');
        if (! $productId) {
            return $this->failure('product_id is required.', [], 422);
        }

        $product = Product::query()->where('is_active', true)->find($productId);
        if (! $product) {
            return $this->failure('Product not found.', [], 404);
        }

        $latest = PriceSnapshot::query()
            ->select('market_id', DB::raw('MAX(snapshot_date) as md'))
            ->where('product_id', $productId)
            ->groupBy('market_id');

        $rows = PriceSnapshot::query()
            ->joinSub($latest, 'agg', function ($join) {
                $join->on('price_snapshots.market_id', '=', 'agg.market_id')
                    ->on('price_snapshots.snapshot_date', '=', 'agg.md');
            })
            ->where('price_snapshots.product_id', $productId)
            ->with('market')
            ->get()
            ->map(fn (PriceSnapshot $s) => [
                'market' => [
                    'id' => $s->market?->id,
                    'name' => $s->market?->name,
                    'area' => $s->market?->area,
                    'lat' => $s->market?->latitude,
                    'lng' => $s->market?->longitude,
                ],
                'avg_price' => (float) $s->avg_price,
                'snapshot_date' => $s->snapshot_date->toDateString(),
            ])
            ->values();

        return $this->success([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'unit' => $product->unit,
            ],
            'markets' => $rows,
        ]);
    }
}
