<?php

namespace App\Services;

use App\Models\Market;
use App\Models\PriceSubmission;
use App\Models\Product;
use App\Models\User;
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
     *     auto_approved: bool,
     *     points_awarded: int,
     *     is_geoverified: bool,
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
        $quantityUnit = trim((string) ($input['quantity_unit'] ?? '')) ?: $product?->unit;
        $price = (float) $input['price'];
        $pricePerUnit = round($price / $quantityValue, 2);

        // A price can only be reported while physically near its selected market.
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
        $auto = (!$isOutlier && $trustData['can_auto_approve']);

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

        // 4. Gamification points: 5 base + 5 for GPS on-site proof
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
            'auto_approved' => $auto,
            'points_awarded' => $pointsToAward,
            'is_geoverified' => $isGeoverified,
            'is_outlier' => $isOutlier,
            'outlier_reason' => $outlierReason,
            'trust_score' => $trustData['score'],
        ];
    }

}
