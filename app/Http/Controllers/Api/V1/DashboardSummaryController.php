<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\PriceSnapshot;
use App\Models\PriceSubmission;
use App\Models\Product;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardSummaryController extends Controller
{
    use ApiResponse;

    public function __invoke(AnalyticsService $analytics): JsonResponse
    {
        $today = Carbon::today();

        // 1. KPI Counts
        $todaySubmissionsCount = PriceSubmission::query()
            ->where('submitted_at', '>=', $today)
            ->count();

        $activeMarketsCount = Market::query()->where('is_active', true)->count();
        $productsTrackedCount = Product::query()->where('is_active', true)->count();

        // 2. Inflation Movers
        $movers = $analytics->inflationMovers(7);
        $topDrop = collect($movers)->where('change_percent', '<', 0)->sortBy('change_percent')->first();
        $topGainer = collect($movers)->where('change_percent', '>', 0)->sortByDesc('change_percent')->first();

        $avgWeeklyChange = count($movers) > 0
            ? round(collect($movers)->avg('change_percent'), 1)
            : 0.0;

        // 3. Top Arbitrage Opportunity (Biggest spread between min and max price today/this week)
        $arbitrageRow = PriceSnapshot::query()
            ->select('product_id', DB::raw('(MAX(avg_price) - MIN(avg_price)) as price_gap'), DB::raw('MIN(avg_price) as min_p'), DB::raw('MAX(avg_price) as max_p'))
            ->where('snapshot_date', '>=', now()->subDays(7))
            ->where('avg_price', '>', 0)
            ->groupBy('product_id')
            ->havingRaw('COUNT(DISTINCT market_id) >= 2')
            ->orderByDesc('price_gap')
            ->first();

        $topArbitrage = null;
        if ($arbitrageRow) {
            $product = Product::query()->find($arbitrageRow->product_id);
            if ($product && (float) $arbitrageRow->price_gap > 0) {
                $topArbitrage = [
                    'product_name' => $product->name,
                    'unit' => $product->unit,
                    'price_gap' => round((float) $arbitrageRow->price_gap, 0),
                    'min_price' => round((float) $arbitrageRow->min_p, 0),
                    'max_price' => round((float) $arbitrageRow->max_p, 0),
                    'percentage_difference' => round(((float) $arbitrageRow->price_gap / (float) $arbitrageRow->min_p) * 100, 1),
                ];
            }
        }

        // 4. Live Ticker Data (Top commodities with current price & trend)
        $latestSnapshots = PriceSnapshot::query()
            ->with(['product', 'market'])
            ->where('snapshot_date', '>=', now()->subDays(7))
            ->orderByDesc('snapshot_date')
            ->get()
            ->groupBy('product_id')
            ->take(10);

        $ticker = [];
        foreach ($latestSnapshots as $group) {
            $first = $group->first();
            if ($first && $first->product) {
                $productMover = collect($movers)->firstWhere('product_id', $first->product_id);
                $ticker[] = [
                    'product_id' => $first->product_id,
                    'product_name' => $first->product->name,
                    'unit' => $first->product->unit,
                    'avg_price' => (float) $first->avg_price,
                    'market_name' => $first->market?->name ?? 'Abuja',
                    'change_percent' => $productMover['change_percent'] ?? 0.0,
                ];
            }
        }

        // 5. Recent Verified Activity Feed (Capped at 7, latest on top)
        $recentActivity = PriceSubmission::query()
            ->with(['product', 'market'])
            ->where('status', PriceSubmission::STATUS_APPROVED)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->take(7)
            ->get()
            ->map(fn ($sub) => [
                'id' => $sub->id,
                'product_name' => $sub->product?->name ?? 'Product',
                'market_name' => $sub->market?->name ?? 'Market',
                'price_per_unit' => (float) ($sub->price_per_unit ?: $sub->price),
                'unit' => $sub->quantity_unit ?? ($sub->product?->unit ?? 'unit'),
                'is_geoverified' => (bool) $sub->is_geoverified,
                'submitted_at' => $sub->submitted_at?->diffForHumans() ?? 'recently',
            ]);

        return $this->success([
            'kpis' => [
                'today_submissions_count' => $todaySubmissionsCount,
                'active_markets_count' => $activeMarketsCount,
                'products_tracked_count' => $productsTrackedCount,
                'weekly_inflation_rate' => $avgWeeklyChange,
                'top_price_drop' => $topDrop,
                'top_price_gainer' => $topGainer,
                'top_arbitrage' => $topArbitrage,
            ],
            'live_ticker' => $ticker,
            'recent_activity' => $recentActivity,
        ], 'Dashboard summary loaded.');
    }
}
