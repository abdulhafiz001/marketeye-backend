<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\AirtimeClaim;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $balance = (int) $user->wallet_balance;
        $min = AirtimeClaim::MIN_CLAIM_AMOUNT;

        $claims = AirtimeClaim::query()
            ->where('user_id', $user->id)
            ->orderByDesc('claimed_at')
            ->limit(50)
            ->get()
            ->map(fn (AirtimeClaim $c) => [
                'id' => $c->id,
                'amount' => (int) $c->amount,
                'phone' => $c->phone,
                'status' => $c->status,
                'admin_note' => $c->admin_note,
                'claimed_at' => $c->claimed_at?->toIso8601String(),
                'paid_at' => $c->paid_at?->toIso8601String(),
            ]);

        return $this->success([
            'wallet' => [
                'balance' => $balance,
                'min_claim' => $min,
                'can_claim' => $balance >= $min,
                'progress' => min(100, (int) round(($balance / $min) * 100)),
                'remaining_to_claim' => max(0, $min - $balance),
            ],
            'claims' => $claims,
        ]);
    }

    public function claim(Request $request, WalletService $wallet): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'min:10', 'max:20', 'regex:/^(\+?234|0)[789][01]\d{8}$/'],
        ]);

        $claim = $wallet->claim($request->user(), $data['phone']);
        $request->user()->refresh();

        return $this->success([
            'claim' => [
                'id' => $claim->id,
                'amount' => (int) $claim->amount,
                'phone' => $claim->phone,
                'status' => $claim->status,
                'claimed_at' => $claim->claimed_at?->toIso8601String(),
            ],
            'wallet' => [
                'balance' => (int) $request->user()->wallet_balance,
            ],
        ], 'Airtime claim submitted. An admin will send airtime soon.');
    }
}
