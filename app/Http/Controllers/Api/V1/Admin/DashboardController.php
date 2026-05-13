<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\PriceSnapshot;
use App\Models\PriceSubmission;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __invoke(): JsonResponse
    {
        $today = Carbon::today();

        $submissionsToday = PriceSubmission::query()
            ->whereDate('submitted_at', $today)
            ->count();

        $pending = PriceSubmission::query()
            ->where('status', PriceSubmission::STATUS_PENDING)
            ->count();

        $weekAgo = $today->copy()->subDays(7);
        $avgThisWeek = (float) (PriceSnapshot::query()
            ->where('snapshot_date', '>=', $weekAgo)
            ->avg('avg_price') ?? 0);

        $avgPrevWindow = (float) (PriceSnapshot::query()
            ->whereBetween('snapshot_date', [$weekAgo->copy()->subDays(7), $weekAgo])
            ->avg('avg_price') ?? 0);

        $pctChange = $avgPrevWindow > 0
            ? round((($avgThisWeek - $avgPrevWindow) / $avgPrevWindow) * 100, 2)
            : 0.0;

        $submissionsPerDay = PriceSubmission::query()
            ->selectRaw('DATE(submitted_at) as d, COUNT(*) as c')
            ->where('submitted_at', '>=', now()->subDays(30))
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->map(fn ($r) => ['date' => (string) $r->d, 'count' => (int) $r->c]);

        $categoryChanges = DB::table('price_snapshots as ps')
            ->join('products as p', 'p.id', '=', 'ps.product_id')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->where('ps.snapshot_date', '>=', $weekAgo)
            ->selectRaw('c.name as category, AVG(ps.avg_price) as avg_price')
            ->groupBy('c.id', 'c.name')
            ->orderBy('c.name')
            ->get()
            ->map(fn ($r) => ['category' => $r->category, 'avg_price' => round((float) $r->avg_price, 2)]);

        return $this->success([
            'total_users' => User::query()->count(),
            'submissions_today' => $submissionsToday,
            'pending_approvals' => $pending,
            'active_markets' => Market::query()->where('is_active', true)->count(),
            'products_count' => Product::query()->where('is_active', true)->count(),
            'price_change_percent_this_week' => $pctChange,
            'charts' => [
                'submissions_last_30_days' => $submissionsPerDay,
                'avg_price_by_category_this_week' => $categoryChanges,
            ],
        ]);
    }
}
