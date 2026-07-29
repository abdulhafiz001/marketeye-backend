<?php

namespace App\Services;

use App\Models\PriceSubmission;
use App\Models\Product;
use App\Models\User;

class PriceSubmissionService
{
    public function __construct(
        private readonly GamificationService $gamification,
        private readonly WalletService $wallet,
    ) {}

    /**
     * @param  array{product_id:int, market_id:int, price:float|int|string, quantity_value?:float|int|string|null, quantity_unit?:string|null, notes?:string|null}  $input
     * @return array{submission: PriceSubmission, auto_approved: bool, points_awarded: int}
     */
    public function submit(User $user, array $input): array
    {
        $productId = (int) $input['product_id'];
        $marketId = (int) $input['market_id'];
        $product = Product::query()->find($productId);
        $quantityValue = max((float) ($input['quantity_value'] ?? 1), 0.001);
        $quantityUnit = trim((string) ($input['quantity_unit'] ?? '')) ?: $product?->unit;
        $price = (float) $input['price'];
        $pricePerUnit = round($price / $quantityValue, 2);

        $auto = $this->gamification->shouldAutoApprove($user, $productId, $marketId);

        $submission = PriceSubmission::query()->create([
            'product_id' => $productId,
            'market_id' => $marketId,
            'user_id' => $user->id,
            'price' => $price,
            'quantity_value' => $quantityValue,
            'quantity_unit' => $quantityUnit,
            'price_per_unit' => $pricePerUnit,
            'notes' => $input['notes'] ?? null,
            'status' => $auto ? PriceSubmission::STATUS_APPROVED : PriceSubmission::STATUS_PENDING,
            'submitted_at' => now(),
            'reviewed_at' => $auto ? now() : null,
            'reviewed_by' => null,
            'wallet_rewarded' => false,
        ]);

        $this->gamification->awardPointsOnSubmission($user, 5);
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
            'points_awarded' => 5,
        ];
    }
}
