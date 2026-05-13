<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\PriceSnapshot;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class PriceTrendingController extends Controller
{
    use ApiResponse;

    public function __invoke(): JsonResponse
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $todayGroups = PriceSnapshot::query()
            ->whereDate('snapshot_date', $today)
            ->get()
            ->groupBy('product_id');

        $yesterdayGroups = PriceSnapshot::query()
            ->whereDate('snapshot_date', $yesterday)
            ->get()
            ->groupBy('product_id');

        $changes = [];

        foreach ($todayGroups as $productId => $rows) {
            $todayAvg = (float) $rows->avg('avg_price');
            $prev = $yesterdayGroups->get($productId);
            if (! $prev || $prev->isEmpty()) {
                continue;
            }

            $prevAvg = (float) $prev->avg('avg_price');
            if ($prevAvg <= 0) {
                continue;
            }

            $pct = (($todayAvg - $prevAvg) / $prevAvg) * 100;
            $changes[] = [
                'product_id' => (int) $productId,
                'change_percent' => round($pct, 2),
            ];
        }

        usort($changes, fn ($a, $b) => abs($b['change_percent']) <=> abs($a['change_percent']));

        $top = array_slice($changes, 0, 15);
        $products = Product::query()->whereIn('id', array_column($top, 'product_id'))->get()->keyBy('id');

        $items = [];
        foreach ($top as $row) {
            $p = $products->get($row['product_id']);
            $items[] = [
                'product' => $p ? [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'unit' => $p->unit,
                ] : null,
                'change_percent' => $row['change_percent'],
            ];
        }

        return $this->success(['trending' => $items]);
    }
}
