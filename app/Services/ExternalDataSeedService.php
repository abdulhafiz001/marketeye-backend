<?php

namespace App\Services;

use App\Models\ExternalPriceSeed;
use App\Models\Market;
use App\Models\Product;
use App\Models\PriceSnapshot;

class ExternalDataSeedService
{
    public function __construct(
        private readonly PriceSnapshotService $snapshots,
        private readonly AdminActivityLogger $logger
    ) {}

    /**
     * Create pending review rows for product+market pairs with stale or missing crowd snapshots.
     */
    public function runStaleSnapshotReview(?\App\Models\Admin $actor = null): array
    {
        $imported = $this->createReviewSeedsForStaleSnapshots('stale_review');
        $status = $imported > 0 ? 'success' : 'partial';
        $message = $imported > 0
            ? 'Created review items where snapshots were stale and crowd data was missing.'
            : 'No stale product/market pairs needed review items.';

        if ($actor) {
            $this->logger->log($actor, 'seed_external_stale_review', 'external_seed', null, [
                'status' => $status,
                'records' => $imported,
                'message' => $message,
            ]);
        }

        return [
            'source' => 'stale_review',
            'status' => $status,
            'records_imported' => $imported,
            'message' => $message,
        ];
    }

    /**
     * Weekly job: for product+market pairs whose latest snapshot is older than 7 days and has no crowd data recently, insert external placeholder.
     */
    public function weeklyFallbackForStaleSnapshots(): int
    {
        return $this->createReviewSeedsForStaleSnapshots('weekly_job');
    }

    private function createReviewSeedsForStaleSnapshots(string $sourceTag): int
    {
        $cutoff = now()->subDays(7)->toDateString();
        $markets = Market::query()->where('is_active', true)->pluck('id');
        $products = Product::query()->where('is_active', true)->limit(30)->get();

        $created = 0;
        foreach ($markets as $marketId) {
            foreach ($products as $product) {
                $latest = PriceSnapshot::query()
                    ->where('product_id', $product->id)
                    ->where('market_id', $marketId)
                    ->orderByDesc('snapshot_date')
                    ->first();

                if ($latest && $latest->snapshot_date->toDateString() >= $cutoff) {
                    continue;
                }

                if ($latest && $latest->snapshot_source === PriceSnapshot::SOURCE_SUBMISSION_AGGREGATE && (int) $latest->submission_count > 0) {
                    continue;
                }

                $base = 500 + (($product->id * 13 + (int) $marketId * 7) % 4000);
                $normalized = (float) $base;

                $exists = ExternalPriceSeed::query()
                    ->where('product_id', $product->id)
                    ->where('market_id', $marketId)
                    ->where('effective_date', now()->toDateString())
                    ->where('status', ExternalPriceSeed::STATUS_PENDING)
                    ->exists();

                if ($exists) {
                    continue;
                }

                ExternalPriceSeed::query()->create([
                    'product_id' => $product->id,
                    'market_id' => $marketId,
                    'source' => 'manual',
                    'raw_price' => $normalized,
                    'normalized_price' => $normalized,
                    'effective_date' => now()->toDateString(),
                    'status' => ExternalPriceSeed::STATUS_PENDING,
                ]);
                $created++;
            }
        }

        return $created;
    }
}
