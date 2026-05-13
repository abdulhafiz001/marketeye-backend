<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SubmitPriceRequest;
use App\Models\PriceSubmission;
use App\Models\Product;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;

class PriceSubmitController extends Controller
{
    use ApiResponse;

    public function store(SubmitPriceRequest $request, GamificationService $gamification): JsonResponse
    {
        $user = $request->user();
        $productId = (int) $request->input('product_id');
        $marketId = (int) $request->input('market_id');
        $product = Product::query()->find($productId);
        $quantityValue = max((float) $request->input('quantity_value', 1), 0.001);
        $quantityUnit = trim((string) $request->input('quantity_unit', '')) ?: $product?->unit;
        $price = (float) $request->input('price');
        $pricePerUnit = round($price / $quantityValue, 2);

        $auto = $gamification->shouldAutoApprove($user, $productId, $marketId);

        $submission = PriceSubmission::query()->create([
            'product_id' => $productId,
            'market_id' => $marketId,
            'user_id' => $user->id,
            'price' => $price,
            'quantity_value' => $quantityValue,
            'quantity_unit' => $quantityUnit,
            'price_per_unit' => $pricePerUnit,
            'notes' => $request->input('notes'),
            'status' => $auto ? PriceSubmission::STATUS_APPROVED : PriceSubmission::STATUS_PENDING,
            'submitted_at' => now(),
            'reviewed_at' => $auto ? now() : null,
            'reviewed_by' => null,
        ]);

        $gamification->awardPointsOnSubmission($user, 5);
        $gamification->recordSubmissionDayAndStreak($user->fresh());

        if ($auto) {
            $gamification->recomputeSnapshotIfApproved($submission->fresh());
        }

        $user->refresh();

        return $this->success([
            'submission' => [
                'id' => $submission->id,
                'status' => $submission->status,
                'points_awarded' => 5,
                'auto_approved' => $auto,
                'quantity_value' => (float) $submission->quantity_value,
                'quantity_unit' => $submission->quantity_unit,
                'price_per_unit' => (float) $submission->price_per_unit,
            ],
            'user' => [
                'points' => (int) $user->points,
            ],
        ], 'Price submitted.');
    }
}
