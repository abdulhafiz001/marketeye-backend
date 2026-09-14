<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\PriceConfirmation;
use App\Models\PriceSnapshot;
use App\Models\Product;
use App\Services\ConfidenceEngineService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ConfidenceEngineService $confidenceEngine
    ) {}

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

    public function show(Request $request, int $id): JsonResponse
    {
        $product = Product::query()
            ->where('is_active', true)
            ->with('category')
            ->find($id);

        if (! $product) {
            return $this->failure('Product not found.', [], 404);
        }

        $filterMarketId = $request->integer('market_id') ?: null;

        $latestByMarket = PriceSnapshot::query()
            ->select('market_id', DB::raw('MAX(snapshot_date) as latest_date'))
            ->where('product_id', $product->id)
            ->groupBy('market_id');

        // Robust auth resolution for public/hybrid endpoint
        $authUser = auth('sanctum')->user() ?? $request->user('sanctum') ?? $request->user();
        if (! $authUser && $request->bearerToken()) {
            $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
            if ($tokenModel && $tokenModel->tokenable instanceof \App\Models\User) {
                $authUser = $tokenModel->tokenable;
            }
        }

        $markets = PriceSnapshot::query()
            ->joinSub($latestByMarket, 'latest', function ($join) {
                $join->on('price_snapshots.market_id', '=', 'latest.market_id')
                    ->on('price_snapshots.snapshot_date', '=', 'latest.latest_date');
            })
            ->where('price_snapshots.product_id', $product->id)
            ->with('market')
            ->orderBy('avg_price')
            ->get()
            ->map(function (PriceSnapshot $snapshot) use ($product, $authUser) {
                $eval = $this->confidenceEngine->evaluate($product->id, (int) $snapshot->market_id, $snapshot);

                $userAction = null;
                if ($authUser) {
                    $userAction = PriceConfirmation::query()
                        ->where('user_id', $authUser->id)
                        ->where('product_id', $product->id)
                        ->where('market_id', (int) $snapshot->market_id)
                        ->value('action');
                }

                return [
                    'market' => [
                        'id' => $snapshot->market?->id,
                        'name' => $snapshot->market?->name,
                        'area' => $snapshot->market?->area,
                    ],
                    'avg_price' => (float) $snapshot->avg_price,
                    'min_price' => (float) $snapshot->min_price,
                    'max_price' => (float) $snapshot->max_price,
                    'snapshot_date' => $snapshot->snapshot_date?->toDateString(),
                    'as_of' => $snapshot->snapshot_date?->format('M j, Y'),
                    'submission_count' => (int) $snapshot->submission_count,
                    'confidence_score' => $eval['score'],
                    'confidence_level' => $eval['level'],
                    'confirmations_count' => $eval['confirmations_count'],
                    'disputes_count' => $eval['disputes_count'],
                    'observations_count' => $eval['observations_count'],
                    'observed_range' => $eval['observed_range'],
                    'last_observed_ago' => $eval['last_observed_ago'],
                    'verdict' => $eval['verdict'],
                    'user_action' => $userAction,
                    'signals' => $eval['signals'],
                ];
            })
            ->values();

        $historyQuery = PriceSnapshot::query()
            ->where('product_id', $product->id)
            ->where('snapshot_date', '>=', now()->subDays(90)->toDateString());

        if ($filterMarketId) {
            $historyQuery->where('market_id', $filterMarketId);
        }

        $historyRows = $historyQuery
            ->selectRaw('snapshot_date, AVG(avg_price) as avg_price, MIN(min_price) as min_price, MAX(max_price) as max_price, SUM(submission_count) as submission_count')
            ->groupBy('snapshot_date')
            ->orderBy('snapshot_date')
            ->get();

        $history = [];
        $changes = [];
        $previous = null;

        foreach ($historyRows as $row) {
            $date = (string) $row->snapshot_date;
            $avg = round((float) $row->avg_price, 2);
            $point = [
                'date' => $date,
                'label' => Carbon::parse($date)->format('M j, Y'),
                'avg_price' => $avg,
                'min_price' => round((float) $row->min_price, 2),
                'max_price' => round((float) $row->max_price, 2),
                'submission_count' => (int) $row->submission_count,
            ];
            $history[] = $point;

            if ($previous === null) {
                $changes[] = [
                    'date' => $date,
                    'label' => $point['label'],
                    'price' => $avg,
                    'previous_price' => null,
                    'change_amount' => null,
                    'change_percent' => null,
                    'direction' => 'start',
                    'note' => 'First recorded price on this day',
                ];
            } else {
                $delta = round($avg - $previous, 2);
                if (abs($delta) >= 0.01) {
                    $pct = $previous > 0 ? round(($delta / $previous) * 100, 2) : null;
                    $changes[] = [
                        'date' => $date,
                        'label' => $point['label'],
                        'price' => $avg,
                        'previous_price' => $previous,
                        'change_amount' => $delta,
                        'change_percent' => $pct,
                        'direction' => $delta > 0 ? 'up' : 'down',
                        'note' => $delta > 0
                            ? 'Price rose on this day'
                            : 'Price fell on this day',
                    ];
                }
            }

            $previous = $avg;
        }

        // Newest changes first for the timeline UI.
        $changes = array_reverse($changes);

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
                'history_market_id' => $filterMarketId,
            ],
            'history' => $history,
            'price_changes' => $changes,
            'markets' => $markets,
        ]);
    }
}
