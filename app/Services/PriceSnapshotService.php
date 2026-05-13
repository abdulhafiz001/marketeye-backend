<?php

namespace App\Services;

use App\Models\PriceSnapshot;
use App\Models\PriceSubmission;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PriceSnapshotService
{
    public function recomputeFromApprovedSubmissions(int $productId, int $marketId, ?Carbon $since = null): ?PriceSnapshot
    {
        $since = $since ?? now()->subHours(48);

        $prices = PriceSubmission::query()
            ->where('product_id', $productId)
            ->where('market_id', $marketId)
            ->where('status', PriceSubmission::STATUS_APPROVED)
            ->where('submitted_at', '>=', $since)
            ->pluck('price_per_unit')
            ->map(fn ($p) => (float) $p)
            ->filter(fn (float $p) => $p > 0)
            ->values();

        if ($prices->isEmpty()) {
            return null;
        }

        $count = $prices->count();
        $avg = round($prices->avg(), 2);
        $min = round($prices->min(), 2);
        $max = round($prices->max(), 2);
        $lowConfidence = $count < 2;

        return $this->upsertSnapshot(
            $productId,
            $marketId,
            $avg,
            $min,
            $max,
            $count,
            $lowConfidence,
            PriceSnapshot::SOURCE_SUBMISSION_AGGREGATE,
            now()->toDateString()
        );
    }

    public function upsertManualSnapshot(
        int $productId,
        int $marketId,
        float $price,
        string $effectiveDate
    ): PriceSnapshot {
        return $this->upsertSnapshot(
            $productId,
            $marketId,
            $price,
            $price,
            $price,
            1,
            false,
            PriceSnapshot::SOURCE_MANUAL,
            $effectiveDate
        );
    }

    public function upsertExternalSnapshot(
        int $productId,
        ?int $marketId,
        float $normalizedPrice,
        string $effectiveDate
    ): ?PriceSnapshot {
        if ($marketId === null) {
            return null;
        }

        return $this->upsertSnapshot(
            $productId,
            $marketId,
            $normalizedPrice,
            $normalizedPrice,
            $normalizedPrice,
            0,
            true,
            PriceSnapshot::SOURCE_EXTERNAL_SEED,
            $effectiveDate
        );
    }

    /**
     * Daily job: aggregate approved submissions from the last 48 hours per product+market.
     */
    public function runDailyAggregateFromSubmissions(): int
    {
        $since = now()->subHours(48);
        $rows = PriceSubmission::query()
            ->select([
                'product_id',
                'market_id',
                DB::raw('COUNT(*) as c'),
                DB::raw('AVG(COALESCE(price_per_unit, price)) as avg_p'),
                DB::raw('MIN(COALESCE(price_per_unit, price)) as min_p'),
                DB::raw('MAX(COALESCE(price_per_unit, price)) as max_p'),
            ])
            ->where('status', PriceSubmission::STATUS_APPROVED)
            ->where('submitted_at', '>=', $since)
            ->groupBy('product_id', 'market_id')
            ->get();

        $updated = 0;
        foreach ($rows as $row) {
            $lowConfidence = (int) $row->c < 2;
            $this->upsertSnapshot(
                (int) $row->product_id,
                (int) $row->market_id,
                round((float) $row->avg_p, 2),
                round((float) $row->min_p, 2),
                round((float) $row->max_p, 2),
                (int) $row->c,
                $lowConfidence,
                PriceSnapshot::SOURCE_SUBMISSION_AGGREGATE,
                now()->toDateString()
            );
            $updated++;
        }

        return $updated;
    }

    private function upsertSnapshot(
        int $productId,
        int $marketId,
        float $avg,
        float $min,
        float $max,
        int $submissionCount,
        bool $lowConfidence,
        string $source,
        string $snapshotDate
    ): PriceSnapshot {
        return PriceSnapshot::query()->updateOrCreate(
            [
                'product_id' => $productId,
                'market_id' => $marketId,
                'snapshot_date' => $snapshotDate,
            ],
            [
                'avg_price' => $avg,
                'min_price' => $min,
                'max_price' => $max,
                'submission_count' => $submissionCount,
                'low_confidence' => $lowConfidence,
                'snapshot_source' => $source,
            ]
        );
    }
}
