<?php

namespace App\Services;

use App\Models\PriceSubmission;
use App\Models\User;
use Carbon\Carbon;

class GamificationService
{
    public function __construct(
        private readonly PriceSnapshotService $snapshots
    ) {}

    public function awardPointsOnSubmission(User $user, int $points = 5): void
    {
        $user->increment('points', $points);
    }

    public function awardApprovalBonusYesterday(): int
    {
        $start = now()->subDay()->startOfDay();
        $end = now()->subDay()->endOfDay();

        $userIds = PriceSubmission::query()
            ->where('status', PriceSubmission::STATUS_APPROVED)
            ->whereBetween('reviewed_at', [$start, $end])
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $count = 0;
        foreach ($userIds as $uid) {
            User::query()->whereKey($uid)->increment('points', 10);
            $count++;
        }

        return $count;
    }

    public function shouldAutoApprove(User $user, int $productId, int $marketId): bool
    {
        $since = now()->subDays(7);

        $approvedCount = PriceSubmission::query()
            ->where('user_id', $user->id)
            ->where('product_id', $productId)
            ->where('market_id', $marketId)
            ->where('status', PriceSubmission::STATUS_APPROVED)
            ->where('submitted_at', '>=', $since)
            ->count();

        return $approvedCount >= 3;
    }

    public function recordSubmissionDayAndStreak(User $user): void
    {
        $today = Carbon::today();
        $last = $user->last_submission_date;

        if ($last && $last->equalTo($today)) {
            return;
        }

        $yesterday = Carbon::yesterday();
        if ($last && $last->equalTo($yesterday)) {
            $user->submission_streak = (int) $user->submission_streak + 1;
        } else {
            $user->submission_streak = 1;
        }

        $user->last_submission_date = $today;

        if ((int) $user->submission_streak >= 7) {
            $user->points = (int) $user->points + 25;
            $user->submission_streak = 0;
        }

        $user->save();
    }

    public function recomputeSnapshotIfApproved(PriceSubmission $submission): void
    {
        if ($submission->status !== PriceSubmission::STATUS_APPROVED) {
            return;
        }

        $this->snapshots->recomputeFromApprovedSubmissions(
            (int) $submission->product_id,
            (int) $submission->market_id
        );
    }
}
