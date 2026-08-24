<?php

namespace App\Services;

use App\Models\Market;
use App\Models\PriceSubmission;
use App\Models\Product;
use App\Models\User;

class PriceSubmissionService
{
    public function __construct(
        private readonly GamificationService $gamification,
        private readonly WalletService $wallet,
        private readonly AnomalyDetectionService $anomalyDetection,
        private readonly UserTrustScoreService $trustScores,
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

        $quantityValue = max((float) ($input['quantity_value'] ?? 1), 0.001);
        $quantityUnit = trim((string) ($input['quantity_unit'] ?? '')) ?: $product?->unit;
        $price = (float) $input['price'];
        $pricePerUnit = round($price / $quantityValue, 2);

        // 1. Geoverification check using Haversine formula if coordinates provided
        $userLat = isset($input['latitude']) ? (float) $input['latitude'] : null;
        $userLng = isset($input['longitude']) ? (float) $input['longitude'] : null;
        $isGeoverified = (bool) ($input['is_geoverified'] ?? false);

        if ($userLat !== null && $userLng !== null && $market && $market->latitude && $market->longitude) {
            $distanceKm = $this->haversineDistanceKm($userLat, $userLng, (float) $market->latitude, (float) $market->longitude);
            // Within 0.75 km (750 meters) of market center
            $isGeoverified = ($distanceKm <= 0.75);
        }

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

    private function haversineDistanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
