<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\PriceSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function home(): View
    {
        $markets = Market::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $carousels = $markets->map(function (Market $market) {
            $latest = PriceSnapshot::query()
                ->select('product_id', DB::raw('MAX(snapshot_date) as md'))
                ->where('market_id', $market->id)
                ->groupBy('product_id');

            $prices = PriceSnapshot::query()
                ->joinSub($latest, 'agg', function ($join) {
                    $join->on('price_snapshots.product_id', '=', 'agg.product_id')
                        ->on('price_snapshots.snapshot_date', '=', 'agg.md');
                })
                ->where('price_snapshots.market_id', $market->id)
                ->with('product')
                ->orderBy('price_snapshots.avg_price')
                ->limit(12)
                ->get()
                ->map(fn (PriceSnapshot $s) => [
                    'name' => $s->product?->name ?? 'Item',
                    'unit' => $s->product?->unit ?? '',
                    'price' => (float) $s->avg_price,
                    'confidence' => (bool) $s->low_confidence ? 'low' : 'good',
                ]);

            return [
                'market' => $market,
                'prices' => $prices,
            ];
        })->filter(fn ($row) => $row['prices']->isNotEmpty())->values();

        return view('site.landing', [
            'carousels' => $carousels,
            'marketCount' => $markets->count(),
        ]);
    }

    public function developers(): View
    {
        return view('site.developers', [
            'baseUrl' => rtrim(config('app.url'), '/').'/api/v1/public',
        ]);
    }
}
