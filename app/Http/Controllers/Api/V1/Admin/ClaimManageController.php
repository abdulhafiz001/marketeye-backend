<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\AirtimeClaim;
use App\Services\AdminActivityLogger;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClaimManageController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $q = AirtimeClaim::query()->with(['user', 'payer']);

        if ($request->query('status')) {
            $q->where('status', $request->query('status'));
        }

        $rows = $q->orderByDesc('claimed_at')->limit(200)->get()->map(fn (AirtimeClaim $c) => [
            'id' => $c->id,
            'amount' => (int) $c->amount,
            'phone' => $c->phone,
            'status' => $c->status,
            'admin_note' => $c->admin_note,
            'claimed_at' => $c->claimed_at?->toIso8601String(),
            'paid_at' => $c->paid_at?->toIso8601String(),
            'user' => $c->user ? [
                'id' => $c->user->id,
                'name' => $c->user->name,
                'email' => $c->user->email,
            ] : null,
            'payer' => $c->payer ? ['id' => $c->payer->id, 'name' => $c->payer->name] : null,
        ]);

        return $this->success(['claims' => $rows]);
    }

    public function markPaid(int $id, Request $request, WalletService $wallet, AdminActivityLogger $logger): JsonResponse
    {
        $claim = AirtimeClaim::query()->find($id);
        if (! $claim) {
            return $this->failure('Not found.', [], 404);
        }

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $claim = $wallet->markPaid($claim, $request->user(), $data['admin_note'] ?? null);
        $logger->log($request->user(), 'Marked airtime claim paid', AirtimeClaim::class, $claim->id);

        return $this->success(['claim' => ['id' => $claim->id, 'status' => $claim->status]]);
    }

    public function reject(int $id, Request $request, WalletService $wallet, AdminActivityLogger $logger): JsonResponse
    {
        $claim = AirtimeClaim::query()->find($id);
        if (! $claim) {
            return $this->failure('Not found.', [], 404);
        }

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $claim = $wallet->reject($claim, $request->user(), $data['admin_note'] ?? null);
        $logger->log($request->user(), 'Rejected airtime claim', AirtimeClaim::class, $claim->id);

        return $this->success(['claim' => ['id' => $claim->id, 'status' => $claim->status]]);
    }
}
