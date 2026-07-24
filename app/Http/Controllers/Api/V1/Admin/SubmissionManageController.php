<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\RejectSubmissionRequest;
use App\Models\PriceSubmission;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionManageController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $q = PriceSubmission::query()->with(['product', 'market', 'user']);

        if ($request->query('status')) {
            $q->where('status', $request->query('status'));
        }
        if ($request->query('market')) {
            $q->where('market_id', (int) $request->query('market'));
        }
        if ($request->query('product')) {
            $q->where('product_id', (int) $request->query('product'));
        }

        $rows = $q->orderByDesc('submitted_at')->limit(200)->get()->map(fn ($s) => [
            'id' => $s->id,
            'status' => $s->status,
            'price' => (float) $s->price,
            'quantity_value' => (float) $s->quantity_value,
            'quantity_unit' => $s->quantity_unit,
            'price_per_unit' => (float) ($s->price_per_unit ?? $s->price),
            'submitted_at' => $s->submitted_at?->toIso8601String(),
            'product' => ['id' => $s->product?->id, 'name' => $s->product?->name],
            'market' => ['id' => $s->market?->id, 'name' => $s->market?->name],
            'user' => $s->user ? ['id' => $s->user->id, 'name' => $s->user->name] : null,
        ]);

        return $this->success(['submissions' => $rows]);
    }

    public function approve(int $id, Request $request, GamificationService $gamification, \App\Services\WalletService $wallet): JsonResponse
    {
        $submission = PriceSubmission::query()->find($id);
        if (! $submission) {
            return $this->failure('Not found.', [], 404);
        }

        if ($submission->status === PriceSubmission::STATUS_APPROVED) {
            return $this->success(['submission' => ['id' => $submission->id, 'status' => $submission->status]]);
        }

        $data = $request->validate([
            'confidence_level' => ['nullable', 'in:high,medium,low'],
        ]);
        $confidence = $data['confidence_level'] ?? 'medium';

        $submission->update([
            'status' => PriceSubmission::STATUS_APPROVED,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
            'rejection_reason' => null,
        ]);

        $fresh = $submission->fresh();
        $gamification->recomputeSnapshotIfApproved($fresh);

        $snapshot = \App\Models\PriceSnapshot::query()
            ->where('product_id', $fresh->product_id)
            ->where('market_id', $fresh->market_id)
            ->whereDate('snapshot_date', now()->toDateString())
            ->first();

        if ($snapshot) {
            if ($confidence === 'high') {
                $snapshot->update([
                    'low_confidence' => false,
                    'submission_count' => max((int) $snapshot->submission_count, 3),
                ]);
            } elseif ($confidence === 'low') {
                $snapshot->update(['low_confidence' => true]);
            } else {
                $snapshot->update([
                    'low_confidence' => false,
                    'submission_count' => max(1, min((int) $snapshot->submission_count, 2)),
                ]);
            }
        }

        $wallet->awardForVerifiedSubmission($fresh);

        return $this->success([
            'submission' => [
                'id' => $submission->id,
                'status' => $submission->status,
                'confidence_level' => $confidence,
            ],
        ]);
    }

    public function reject(int $id, RejectSubmissionRequest $request): JsonResponse
    {
        $submission = PriceSubmission::query()->find($id);
        if (! $submission) {
            return $this->failure('Not found.', [], 404);
        }

        $submission->update([
            'status' => PriceSubmission::STATUS_REJECTED,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
            'rejection_reason' => $request->string('reason')->toString(),
        ]);

        return $this->success(['submission' => ['id' => $submission->id, 'status' => $submission->status]]);
    }

    public function bulkApprove(Request $request, GamificationService $gamification, \App\Services\WalletService $wallet): JsonResponse
    {
        $ids = $request->input('ids', []);
        if (! is_array($ids) || empty($ids)) {
            return $this->failure('ids array required.', [], 422);
        }

        foreach ($ids as $id) {
            $submission = PriceSubmission::query()->find((int) $id);
            if (! $submission || $submission->status === PriceSubmission::STATUS_APPROVED) {
                continue;
            }
            $submission->update([
                'status' => PriceSubmission::STATUS_APPROVED,
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
                'rejection_reason' => null,
            ]);
            $fresh = $submission->fresh();
            $gamification->recomputeSnapshotIfApproved($fresh);
            $wallet->awardForVerifiedSubmission($fresh);
        }

        return $this->success(null, 'Bulk approve completed.');
    }
}
