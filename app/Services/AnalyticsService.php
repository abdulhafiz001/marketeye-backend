<?php

namespace App\Services;

use App\Models\AirtimeClaim;
use App\Models\PriceSnapshot;
use App\Models\PriceSubmission;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function dashboard(): array
    {
        return [
            'inflation' => [
                '30d' => $this->inflationMovers(30),
                '90d' => $this->inflationMovers(90),
            ],
            'submission_heatmap' => $this->submissionHeatmap(90),
            'top_contributors' => $this->topContributors(10),
            'rejection_reasons' => $this->rejectionReasons(30),
            'wallet_liability' => $this->walletLiability(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function inflationMovers(int $days): array
    {
        $end = now()->toDateString();
        $start = now()->subDays($days)->toDateString();
        $mid = now()->subDays((int) floor($days / 2))->toDateString();

        $recent = PriceSnapshot::query()
            ->select('product_id', 'market_id', DB::raw('AVG(avg_price) as avg_p'))
            ->whereBetween('snapshot_date', [$mid, $end])
            ->groupBy('product_id', 'market_id');

        $baseline = PriceSnapshot::query()
            ->select('product_id', 'market_id', DB::raw('AVG(avg_price) as avg_p'))
            ->whereBetween('snapshot_date', [$start, $mid])
            ->groupBy('product_id', 'market_id');

        return DB::query()
            ->fromSub($recent, 'recent')
            ->joinSub($baseline, 'base', function ($join) {
                $join->on('recent.product_id', '=', 'base.product_id')
                    ->on('recent.market_id', '=', 'base.market_id');
            })
            ->join('products', 'products.id', '=', 'recent.product_id')
            ->join('markets', 'markets.id', '=', 'recent.market_id')
            ->where('base.avg_p', '>', 0)
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'products.unit',
                'markets.id as market_id',
                'markets.name as market_name',
                'recent.avg_p as current_avg',
                'base.avg_p as previous_avg',
            ])
            ->get()
            ->map(function ($row) {
                $current = (float) $row->current_avg;
                $previous = (float) $row->previous_avg;
                $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;

                return [
                    'product_id' => (int) $row->product_id,
                    'product_name' => $row->product_name,
                    'unit' => $row->unit,
                    'market_id' => (int) $row->market_id,
                    'market_name' => $row->market_name,
                    'current_avg' => round($current, 2),
                    'previous_avg' => round($previous, 2),
                    'change_percent' => round($change, 2),
                ];
            })
            ->sortByDesc(fn ($row) => abs($row['change_percent']))
            ->take(20)
            ->values()
            ->all();
    }

    /**
     * Daily submission counts for the last N days (heatmap-friendly).
     *
     * @return list<array{date: string, count: int, approved: int, rejected: int, pending: int}>
     */
    public function submissionHeatmap(int $days = 90): array
    {
        $start = now()->subDays($days)->startOfDay();

        $rows = PriceSubmission::query()
            ->selectRaw('DATE(submitted_at) as day')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved")
            ->selectRaw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->where('submitted_at', '>=', $start)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $out = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $row = $rows->get($date);
            $out[] = [
                'date' => $date,
                'count' => (int) ($row->total ?? 0),
                'approved' => (int) ($row->approved ?? 0),
                'rejected' => (int) ($row->rejected ?? 0),
                'pending' => (int) ($row->pending ?? 0),
            ];
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function topContributors(int $limit = 10): array
    {
        return User::query()
            ->select('users.id', 'users.name', 'users.points', 'users.avatar')
            ->selectRaw('COUNT(price_submissions.id) as submission_count')
            ->selectRaw("SUM(CASE WHEN price_submissions.status = 'approved' THEN 1 ELSE 0 END) as approved_count")
            ->leftJoin('price_submissions', 'price_submissions.user_id', '=', 'users.id')
            ->groupBy('users.id', 'users.name', 'users.points', 'users.avatar')
            ->orderByDesc('approved_count')
            ->limit($limit)
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'points' => (int) $u->points,
                'avatar' => $u->avatar,
                'submission_count' => (int) $u->submission_count,
                'approved_count' => (int) $u->approved_count,
            ])
            ->all();
    }

    /**
     * @return list<array{reason: string, count: int}>
     */
    public function rejectionReasons(int $days = 30): array
    {
        return PriceSubmission::query()
            ->selectRaw("COALESCE(NULLIF(TRIM(rejection_reason), ''), 'No reason given') as reason")
            ->selectRaw('COUNT(*) as count')
            ->where('status', PriceSubmission::STATUS_REJECTED)
            ->where('reviewed_at', '>=', now()->subDays($days))
            ->groupBy('reason')
            ->orderByDesc('count')
            ->limit(15)
            ->get()
            ->map(fn ($row) => [
                'reason' => (string) $row->reason,
                'count' => (int) $row->count,
            ])
            ->all();
    }

    public function walletLiability(): array
    {
        $pendingClaims = AirtimeClaim::query()
            ->where('status', AirtimeClaim::STATUS_PENDING);

        $pendingAmount = (int) (clone $pendingClaims)->sum('amount');
        $pendingCount = (clone $pendingClaims)->count();

        $unclaimedWallet = (int) User::query()->sum('wallet_balance');

        return [
            'pending_claims_count' => $pendingCount,
            'pending_claims_amount' => $pendingAmount,
            'unclaimed_wallet_balance' => $unclaimedWallet,
            'total_outstanding_naira' => $pendingAmount + $unclaimedWallet,
            'min_claim_amount' => AirtimeClaim::MIN_CLAIM_AMOUNT,
        ];
    }

    /**
     * Lighter payload for the mobile Insights tab.
     */
    public function publicInsights(): array
    {
        $movers30 = collect($this->inflationMovers(30))->take(8)->values()->all();
        $heatmap = collect($this->submissionHeatmap(30))->all();

        return [
            'inflation_30d' => $movers30,
            'submission_heatmap_30d' => $heatmap,
            'top_contributors' => $this->topContributors(5),
            'community' => [
                'products_tracked' => Product::query()->where('is_active', true)->count(),
                'submissions_30d' => PriceSubmission::query()
                    ->where('submitted_at', '>=', now()->subDays(30))
                    ->count(),
                'approved_30d' => PriceSubmission::query()
                    ->where('status', PriceSubmission::STATUS_APPROVED)
                    ->where('submitted_at', '>=', now()->subDays(30))
                    ->count(),
            ],
        ];
    }
}
