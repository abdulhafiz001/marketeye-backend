<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\PriceConfirmation;
use App\Models\PriceSnapshot;
use App\Models\Product;
use App\Services\ConfidenceEngineService;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunityPriceValidationController extends Controller
{
    use ApiResponse;

    public function __invoke(
        Request $request,
        ConfidenceEngineService $confidenceEngine,
        GamificationService $gamification
    ): JsonResponse {
        $user = $request->user();

        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'market_id' => ['required', 'integer', 'exists:markets,id'],
            'action' => ['required', 'string', 'in:confirm,dispute,CONFIRM,DISPUTE'],
            'reported_price' => ['nullable', 'numeric', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $productId = (int) $data['product_id'];
        $marketId = (int) $data['market_id'];
        $action = strtoupper($data['action']);
        $market = Market::query()->find($marketId);

        // Geoverification check
        $isGeoverified = false;
        $userLat = isset($data['latitude']) ? (float) $data['latitude'] : null;
        $userLng = isset($data['longitude']) ? (float) $data['longitude'] : null;
        if ($userLat !== null && $userLng !== null && $market && $market->latitude && $market->longitude) {
            $dist = $this->haversineKm($userLat, $userLng, (float) $market->latitude, (float) $market->longitude);
            $isGeoverified = ($dist <= 0.75); // Within 750m
        }

        // Upsert user confirmation / dispute
        $confirmation = PriceConfirmation::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'product_id' => $productId,
                'market_id' => $marketId,
            ],
            [
                'action' => $action,
                'reported_price' => isset($data['reported_price']) ? (float) $data['reported_price'] : null,
                'notes' => $data['notes'] ?? null,
                'is_geoverified' => $isGeoverified,
                'latitude' => $userLat,
                'longitude' => $userLng,
            ]
        );

        // Award community points for crowdsourced validation (+2 base, +3 bonus if in-market)
        $points = $isGeoverified ? 5 : 2;
        $gamification->awardPointsOnSubmission($user, $points);

        // Fetch latest snapshot
        $snapshot = PriceSnapshot::query()
            ->where('product_id', $productId)
            ->where('market_id', $marketId)
            ->orderByDesc('snapshot_date')
            ->first();

        // Evaluate continuous confidence
        $eval = $confidenceEngine->evaluate($productId, $marketId, $snapshot);

        if ($snapshot) {
            $snapshot->update([
                'confidence_score' => $eval['score'],
                'confirmations_count' => $eval['confirmations_count'],
                'disputes_count' => $eval['disputes_count'],
                'low_confidence' => in_array($eval['level'], ['low', 'needs_review', 'stale'], true),
            ]);
        }

        $msg = $action === PriceConfirmation::ACTION_CONFIRM
            ? 'Thank you! You confirmed this market price (+'.$points.' points).'
            : 'Price dispute recorded. Our system flagged this for community review (+'.$points.' points).';

        return $this->success([
            'action' => $action,
            'is_geoverified' => $isGeoverified,
            'points_earned' => $points,
            'confidence' => $eval,
        ], $msg);
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}
