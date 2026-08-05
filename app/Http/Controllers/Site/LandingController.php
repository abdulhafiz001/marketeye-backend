<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Developer;
use App\Models\Market;
use App\Models\PriceSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
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
        $appUrl = rtrim((string) config('app.url'), '/');

        return view('site.developers', [
            'baseUrl' => $appUrl.'/api/v1/public',
            'appUrl' => $appUrl,
            'productionBaseUrl' => 'https://marketeye.ahzcode.sbs/api/v1/public',
            'defaultDailyLimit' => Developer::DEFAULT_DAILY_LIMIT,
        ]);
    }

    public function openapi(): JsonResponse
    {
        $path = public_path('openapi/public-api.v1.json');
        $spec = json_decode((string) file_get_contents($path), true) ?: [];
        $base = rtrim((string) config('app.url'), '/').'/api/v1/public';
        $spec['servers'] = [
            [
                'url' => 'https://marketeye.ahzcode.sbs/api/v1/public',
                'description' => 'Production (canonical)',
            ],
            [
                'url' => $base,
                'description' => 'This Market Eye instance (from APP_URL)',
            ],
        ];

        return response()->json($spec);
    }

    public function swagger(): View
    {
        return view('site.swagger', [
            'specUrl' => route('developers.openapi'),
        ]);
    }

    public function postman(): Response
    {
        $path = public_path('openapi/MarketEye.PublicAPI.postman_collection.json');
        $collection = json_decode((string) file_get_contents($path), true) ?: [];
        $base = rtrim(config('app.url'), '/').'/api/v1/public';

        foreach ($collection['variable'] ?? [] as $i => $var) {
            if (($var['key'] ?? null) === 'baseUrl') {
                $collection['variable'][$i]['value'] = $base;
            }
        }

        return response(
            json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            200,
            [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="MarketEye.PublicAPI.postman_collection.json"',
            ]
        );
    }
}
