<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Market;
use App\Models\PriceSnapshot;
use App\Models\Product;
use App\Support\CategoryIcon;
use App\Support\PriceSnapshotPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicMarketDataController extends Controller
{
    use ApiResponse;

    public function markets(): JsonResponse
    {
        $markets = Market::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Market $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'area' => $m->area,
                'city' => $m->city,
                'state' => $m->state,
                'lat' => $m->latitude,
                'lng' => $m->longitude,
                'description' => $m->description,
            ]);

        return $this->success([
            'markets' => $markets,
            'meta' => [
                'units' => ['kg', 'mudu', 'paint', 'bag', 'congo', 'piece', 'litre', 'derica'],
                'currency' => 'NGN',
            ],
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get()
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'icon' => CategoryIcon::resolve($c->icon, $c->name, $c->slug),
                'product_count' => (int) $c->products_count,
            ]);

        return $this->success(['categories' => $categories]);
    }

    public function products(): JsonResponse
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with('category')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'unit' => $p->unit,
                'description' => $p->description,
                'category' => [
                    'id' => $p->category?->id,
                    'name' => $p->category?->name,
                    'slug' => $p->category?->slug,
                ],
            ]);

        return $this->success(['products' => $products]);
    }

    public function marketPrices(Request $request, int $id): JsonResponse
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

        $rows = $query->get()->map(function (PriceSnapshot $s) use ($market) {
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
                ],
                'market' => [
                    'id' => $market->id,
                    'name' => $market->name,
                    'area' => $market->area,
                ],
                'price' => [
                    'avg' => (float) $s->avg_price,
                    'min' => (float) $s->min_price,
                    'max' => (float) $s->max_price,
                    'currency' => 'NGN',
                ],
                'measurement' => [
                    'unit' => $p?->unit,
                    'note' => 'Units reflect how Nigerians buy goods in open markets (kg, mudu, bag, paint, etc.).',
                ],
                'quality' => [
                    'submission_count' => (int) $s->submission_count,
                    'low_confidence' => (bool) $s->low_confidence,
                    'confidence_level' => PriceSnapshotPresenter::confidenceLevel($s),
                    'is_stale' => PriceSnapshotPresenter::isStale($s),
                ],
                'updated_at' => $s->snapshot_date->toDateString(),
            ];
        });

        return $this->success([
            'market' => [
                'id' => $market->id,
                'name' => $market->name,
                'area' => $market->area,
                'city' => $market->city,
                'state' => $market->state,
            ],
            'prices' => $rows,
        ]);
    }
}
