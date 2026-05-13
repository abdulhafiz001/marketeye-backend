<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\PriceSnapshot;
use App\Support\PriceSnapshotPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketPriceController extends Controller
{
    use ApiResponse;

    public function index(Request $request, int $id): JsonResponse
    {
        $market = Market::query()->where('is_active', true)->find($id);
        if (! $market) {
            return $this->failure('Market not found.', [], 404);
        }

        $categorySlug = $request->query('category');
        $search = $request->query('search');

        $latest = PriceSnapshot::query()
            ->select('product_id', DB::raw('MAX(snapshot_date) as md'))
            ->where('market_id', $id)
            ->groupBy('product_id');

        $query = PriceSnapshot::query()
            ->joinSub($latest, 'agg', function ($join) {
                $join->on('price_snapshots.product_id', '=', 'agg.product_id')
                    ->on('price_snapshots.snapshot_date', '=', 'agg.md');
            })
            ->where('price_snapshots.market_id', $id)
            ->with(['product.category']);

        if ($categorySlug) {
            $query->whereHas('product.category', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($search) {
            $query->whereHas('product', fn ($q) => $q->where('name', 'like', '%'.$search.'%'));
        }

        $rows = $query->get()->map(function (PriceSnapshot $s) {
            $p = $s->product;
            $c = $p?->category;

            return [
                'product' => [
                    'id' => $p?->id,
                    'name' => $p?->name,
                    'slug' => $p?->slug,
                    'unit' => $p?->unit,
                ],
                'category' => [
                    'id' => $c?->id,
                    'name' => $c?->name,
                    'slug' => $c?->slug,
                    'icon' => $c?->icon,
                ],
                'avg_price' => (float) $s->avg_price,
                'min_price' => (float) $s->min_price,
                'max_price' => (float) $s->max_price,
                'submission_count' => (int) $s->submission_count,
                'snapshot_date' => $s->snapshot_date->toDateString(),
                'is_stale' => PriceSnapshotPresenter::isStale($s),
                'low_confidence' => (bool) $s->low_confidence,
                'confidence_level' => PriceSnapshotPresenter::confidenceLevel($s),
            ];
        });

        return $this->success([
            'market' => [
                'id' => $market->id,
                'name' => $market->name,
                'area' => $market->area,
                'description' => $market->description,
                'lat' => $market->latitude,
                'lng' => $market->longitude,
            ],
            'prices' => $rows,
        ]);
    }
}
