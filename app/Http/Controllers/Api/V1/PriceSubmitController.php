<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SubmitPriceRequest;
use App\Services\PriceSubmissionService;
use Illuminate\Http\JsonResponse;

class PriceSubmitController extends Controller
{
    use ApiResponse;

    public function store(SubmitPriceRequest $request, PriceSubmissionService $submissions): JsonResponse
    {
        $user = $request->user();
        $created = $submissions->submit($user, $request->validated());
        $submission = $created['submission'];
        $user->refresh();

        return $this->success([
            'submission' => [
                'id' => $submission->id,
                'status' => $submission->status,
                'points_awarded' => $created['points_awarded'],
                'wallet_awarded' => $created['auto_approved'] ? 1 : 0,
                'auto_approved' => $created['auto_approved'],
                'quantity_value' => (float) $submission->quantity_value,
                'quantity_unit' => $submission->quantity_unit,
                'price_per_unit' => (float) $submission->price_per_unit,
            ],
            'user' => [
                'points' => (int) $user->points,
                'wallet_balance' => (int) $user->wallet_balance,
            ],
        ], 'Price submitted.');
    }
}
