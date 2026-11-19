<?php

use App\Models\CatalogSuggestion;
use App\Models\Category;
use App\Models\PriceSnapshot;
use App\Models\PriceSubmission;
use App\Models\Product;
use App\Services\GamificationService;
use App\Services\PriceSnapshotService;
use App\Services\PriceSubmissionService;
use App\Services\WalletService;
use App\Support\CategoryIcon;
use App\Support\UnitNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $submissionService = app(PriceSubmissionService::class);
        $snapshotService = app(PriceSnapshotService::class);
        $gamification = app(GamificationService::class);
        $wallet = app(WalletService::class);

        // 1. Separate any existing price submissions whose quantity_unit does not match their product's unit
        //    (e.g. a "per cup" submission recorded against a "per mudu" product)
        $affectedPairs = [];
        $allSubmissions = PriceSubmission::query()->with('product')->get();
        foreach ($allSubmissions as $sub) {
            $product = $sub->product;
            if (! $product) {
                continue;
            }
            if (! UnitNormalizer::areCompatible($sub->quantity_unit, $product->unit)) {
                $oldProductId = (int) $product->id;
                $marketId = (int) $sub->market_id;
                $variantProduct = $submissionService->resolveOrCreateUnitVariantProduct($product, (string) $sub->quantity_unit);

                $sub->product_id = $variantProduct->id;
                $sub->quantity_unit = $variantProduct->unit;
                $sub->save();

                $affectedPairs["{$oldProductId}:{$marketId}"] = [$oldProductId, $marketId];
                $affectedPairs["{$variantProduct->id}:{$marketId}"] = [(int) $variantProduct->id, $marketId];
            }
        }

        // Recompute snapshots for affected products/markets (and remove stale snapshots if no submissions remain)
        foreach ($affectedPairs as [$pid, $mid]) {
            $recomputed = $snapshotService->recomputeFromApprovedSubmissions($pid, $mid, now()->subDays(90));
            if (! $recomputed) {
                PriceSnapshot::query()
                    ->where('product_id', $pid)
                    ->where('market_id', $mid)
                    ->where('snapshot_source', PriceSnapshot::SOURCE_SUBMISSION_AGGREGATE)
                    ->delete();
            }
        }

        // 2. Automatically publish any pending catalog_suggestions so earlier user-submitted products go live
        $pendingSuggestions = CatalogSuggestion::query()
            ->with(['user', 'category'])
            ->where('status', 'pending')
            ->get();

        foreach ($pendingSuggestions as $suggestion) {
            $category = $suggestion->category;
            if (! $category && $suggestion->category_name) {
                $catName = trim($suggestion->category_name);
                $slug = Str::slug($catName) ?: 'category-'.Str::lower(Str::random(5));
                $category = Category::query()->whereRaw('LOWER(name) = ?', [Str::lower($catName)])->first()
                    ?? Category::query()->create([
                        'name' => $catName,
                        'slug' => $slug,
                        'icon' => CategoryIcon::resolve(null, $catName, $slug),
                    ]);
            }
            if (! $category) {
                $category = Category::query()->first();
            }
            if (! $category) {
                continue;
            }

            $cleanUnit = UnitNormalizer::displayUnit((string) $suggestion->unit);
            $baseName = UnitNormalizer::baseProductName($suggestion->product_name);

            $product = Product::query()
                ->where('is_active', true)
                ->get()
                ->first(fn (Product $p) => Str::lower(UnitNormalizer::baseProductName($p->name)) === Str::lower($baseName)
                    && UnitNormalizer::areCompatible($p->unit, $cleanUnit));

            if (! $product) {
                $hasSameBase = Product::query()
                    ->where('is_active', true)
                    ->whereRaw('LOWER(name) = ?', [Str::lower($baseName)])
                    ->exists();
                $finalName = $hasSameBase ? "{$baseName} ({$cleanUnit})" : $suggestion->product_name;

                $product = Product::query()->create([
                    'category_id' => $category->id,
                    'name' => $finalName,
                    'slug' => Str::slug($finalName).'-'.Str::lower(Str::random(6)),
                    'unit' => $cleanUnit,
                    'description' => $suggestion->reason,
                    'is_active' => true,
                ]);
            }

            foreach ((array) $suggestion->market_prices as $row) {
                if (empty($row['market_id']) || empty($row['price'])) {
                    continue;
                }
                $sub = PriceSubmission::query()->create([
                    'product_id' => $product->id,
                    'market_id' => (int) $row['market_id'],
                    'user_id' => $suggestion->user_id,
                    'price' => (float) $row['price'],
                    'quantity_value' => 1,
                    'quantity_unit' => $product->unit,
                    'price_per_unit' => (float) $row['price'],
                    'is_geoverified' => true,
                    'latitude' => isset($row['latitude']) ? (float) $row['latitude'] : null,
                    'longitude' => isset($row['longitude']) ? (float) $row['longitude'] : null,
                    'is_outlier' => false,
                    'notes' => $suggestion->reason ?: 'Published from user product submission.',
                    'status' => PriceSubmission::STATUS_APPROVED,
                    'submitted_at' => $suggestion->created_at ?? now(),
                    'reviewed_at' => now(),
                    'wallet_rewarded' => false,
                ]);

                if ($suggestion->user) {
                    $gamification->awardPointsOnSubmission($suggestion->user, 10);
                }
                $gamification->recomputeSnapshotIfApproved($sub);
                $wallet->awardForVerifiedSubmission($sub);
            }

            $suggestion->update(['status' => 'approved']);
        }
    }

    public function down(): void
    {
        // Data reconciliation migration
    }
};
