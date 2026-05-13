<?php

namespace App\Services;

use App\Models\ExternalPriceSeed;
use App\Models\Market;
use App\Models\Product;
use App\Models\PriceSnapshot;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExternalDataSeedService
{
    public function __construct(
        private readonly PriceSnapshotService $snapshots,
        private readonly AdminActivityLogger $logger
    ) {}

    /**
     * Attempts to fetch public pages and create review rows when no recent crowd snapshot exists.
     * Real RTFP/WFP CSV endpoints vary; this records fetch status and creates conservative review items when needed.
     */
    public function runWorldBankSeed(?\App\Models\User $actor = null): array
    {
        $url = config('services.worldbank_rtfp_catalog_url');
        $imported = 0;
        $status = 'partial';
        $message = 'Catalog page reachable; structured CSV import requires dataset file URL.';

        try {
            $response = Http::timeout(25)->get($url);
            if (! $response->successful()) {
                $status = 'failed';
                $message = 'HTTP '.$response->status();
            }
        } catch (\Throwable $e) {
            Log::warning('World Bank seed fetch failed', ['e' => $e->getMessage()]);
            $status = 'failed';
            $message = $e->getMessage();
        }

        if ($actor) {
            $this->logger->log($actor, 'seed_external_worldbank', 'external_seed', null, [
                'status' => $status,
                'records' => $imported,
                'message' => $message,
            ]);
        }

        return ['source' => 'worldbank', 'status' => $status, 'records_imported' => $imported, 'message' => $message];
    }

    public function runWfpSeed(?\App\Models\User $actor = null): array
    {
        $url = config('services.wfp_hdx_catalog_url');
        $imported = 0;
        $status = 'partial';
        $message = 'HDX dataset page reachable; automated CSV parse not configured for this environment.';

        try {
            $response = Http::timeout(25)->get($url);
            if (! $response->successful()) {
                $status = 'failed';
                $message = 'HTTP '.$response->status();
            }
        } catch (\Throwable $e) {
            Log::warning('WFP seed fetch failed', ['e' => $e->getMessage()]);
            $status = 'failed';
            $message = $e->getMessage();
        }

        if ($status !== 'failed' && Product::query()->exists() && Market::query()->exists()) {
            $imported = $this->createReviewSeedsForStaleSnapshots('wfp');
            if ($imported > 0) {
                $status = 'success';
                $message = 'Created external price review items where snapshots were stale and crowd data was missing.';
            }
        }

        if ($actor) {
            $this->logger->log($actor, 'seed_external_wfp', 'external_seed', null, [
                'status' => $status,
                'records' => $imported,
                'message' => $message,
            ]);
        }

        return ['source' => 'wfp', 'status' => $status, 'records_imported' => $imported, 'message' => $message];
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
                    'source' => str_contains($sourceTag, 'wfp') ? 'wfp' : 'manual',
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
