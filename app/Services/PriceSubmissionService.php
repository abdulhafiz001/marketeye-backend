<?php

namespace App\Services;

use App\Models\Market;
use App\Models\PriceSubmission;
use App\Models\Product;
use App\Models\User;
use App\Support\UnitNormalizer;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PriceSubmissionService
{
    public function __construct(
        private readonly GamificationService $gamification,
        private readonly WalletService $wallet,
        private readonly AnomalyDetectionService $anomalyDetection,
        private readonly UserTrustScoreService $trustScores,
        private readonly MarketProximityService $marketProximity,
    ) {}

    /**
     * @param  array{
     *     product_id:int,
     *     market_id:int,
     *     price:float|int|string,
     *     quantity_value?:float|int|string|null,
     *     quantity_unit?:string|null,
     *     notes?:string|null,
     *     is_geoverified?:bool|null,
     *     latitude?:float|int|string|null,
     *     longitude?:float|int|string|null
     * }  $input
     * @return array{
     *     submission: PriceSubmission,
     *     product: Product,
     *     auto_approved: bool,
     *     points_awarded: int,
     *     wallet_awarded: int,
     *     is_geoverified: bool,
     *     distance_km: float,
     *     is_outlier: bool,
     *     outlier_reason: ?string,
     *     trust_score: float
     * }
     */
    public function submit(User $user, array $input): array
    {
        $productId = (int) $input['product_id'];
        $marketId = (int) $input['market_id'];
        $product = Product::query()->find($productId);
        $market = Market::query()->find($marketId);

        if (! $product || ! $product->is_active) {
            throw ValidationException::withMessages(['product_id' => ['This product is not available for price reporting.']]);
        }
        if (! $market || ! $market->is_active) {
            throw ValidationException::withMessages(['market_id' => ['This market is not available for price reporting.']]);
        }

        $quantityValue = max((float) ($input['quantity_value'] ?? 1), 0.001);
        $rawUnit = trim((string) ($input['quantity_unit'] ?? ''));
        $quantityUnit = $rawUnit !== '' ? UnitNormalizer::displayUnit($rawUnit) : (string) $product->unit;

        // If the user reported in a different measurement unit (e.g. "cup" on a "mudu" product),
        // route to a dedicated unit-variant product so incompatible units never corrupt each other's price.
        if (! UnitNormalizer::areCompatible($quantityUnit, $product->unit)) {
            $product = $this->resolveOrCreateUnitVariantProduct($product, $quantityUnit);
            $productId = (int) $product->id;
            $quantityUnit = (string) $product->unit;
        } else {
            $quantityUnit = (string) ($product->unit ?: $quantityUnit);
        }

        $price = (float) $input['price'];
        $pricePerUnit = round($price / $quantityValue, 2);

        // A price can only be reported while within the selected market's geofence radius.
        $userLat = isset($input['latitude']) ? (float) $input['latitude'] : null;
        $userLng = isset($input['longitude']) ? (float) $input['longitude'] : null;
        $distanceKm = $this->marketProximity->assertWithinMarket($market, $userLat, $userLng);
        $isGeoverified = true;

        // 2. Statistical Anomaly & Outlier Filter (IQR)
        $anomalyCheck = $this->anomalyDetection->evaluateSubmission($pricePerUnit, $productId, $marketId);
        $isOutlier = $anomalyCheck['is_outlier'];
        $outlierReason = $anomalyCheck['reason'];

        // 3. User Trust Scoring & Dynamic Auto-Approval
        $trustData = $this->trustScores->calculateTrust($user);

        // Outliers are NEVER auto-approved; must be reviewed by moderators
        $auto = (! $isOutlier && $trustData['can_auto_approve']);

        $submission = PriceSubmission::query()->create([
            'product_id' => $productId,
            'market_id' => $marketId,
            'user_id' => $user->id,
            'price' => $price,
            'quantity_value' => $quantityValue,
            'quantity_unit' => $quantityUnit,
            'price_per_unit' => $pricePerUnit,
            'is_geoverified' => $isGeoverified,
            'distance_km' => $distanceKm,
            'latitude' => $userLat,
            'longitude' => $userLng,
            'is_outlier' => $isOutlier,
            'outlier_reason' => $outlierReason,
            'notes' => $input['notes'] ?? null,
            'status' => $auto ? PriceSubmission::STATUS_APPROVED : PriceSubmission::STATUS_PENDING,
            'submitted_at' => now(),
            'reviewed_at' => $auto ? now() : null,
            'reviewed_by' => null,
            'wallet_rewarded' => false,
        ]);

        // 4. Gamification points: 5 base + 5 for GPS proof
        $pointsToAward = $isGeoverified ? 10 : 5;
        $this->gamification->awardPointsOnSubmission($user, $pointsToAward);
        $this->gamification->recordSubmissionDayAndStreak($user->fresh());

        if ($auto) {
            $fresh = $submission->fresh();
            $this->gamification->recomputeSnapshotIfApproved($fresh);
            $this->wallet->awardForVerifiedSubmission($fresh);
        } else {
            app(PendingSubmissionAlertService::class)->notifyIfNeeded();
        }

        return [
            'submission' => $submission->fresh(),
            'product' => $product,
            'auto_approved' => $auto,
            'points_awarded' => $pointsToAward,
            'wallet_awarded' => $auto ? 1 : 0,
            'is_geoverified' => $isGeoverified,
            'distance_km' => $distanceKm,
            'is_outlier' => $isOutlier,
            'outlier_reason' => $outlierReason,
            'trust_score' => $trustData['score'],
        ];
    }

    public function resolveOrCreateUnitVariantProduct(Product $baseProduct, string $targetUnit): Product
    {
        $canonicalTarget = UnitNormalizer::canonical($targetUnit);
        $baseName = UnitNormalizer::baseProductName($baseProduct->name);

        // Look for an existing active product with the same base name and compatible unit
        $candidates = Product::query()
            ->where('is_active', true)
            ->where(function ($q) use ($baseName) {
                $q->whereRaw('LOWER(name) = ?', [Str::lower($baseName)])
                    ->orWhereRaw('LOWER(name) LIKE ?', [Str::lower($baseName).' (%']);
            })
            ->get();

        foreach ($candidates as $candidate) {
            if (
                Str::lower(UnitNormalizer::baseProductName($candidate->name)) === Str::lower($baseName) &&
                UnitNormalizer::areCompatible($candidate->unit, $canonicalTarget)
            ) {
                return $candidate;
            }
        }

        $displayUnit = UnitNormalizer::displayUnit($targetUnit);
        $variantName = "{$baseName} ({$displayUnit})";

        return Product::query()->create([
            'category_id' => $baseProduct->category_id,
            'name' => $variantName,
            'slug' => Str::slug($variantName).'-'.Str::lower(Str::random(5)),
            'unit' => $displayUnit,
            'description' => $baseProduct->description,
            'image' => $baseProduct->image,
            'is_active' => true,
        ]);
    }
}
