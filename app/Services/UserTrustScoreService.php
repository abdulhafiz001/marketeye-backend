<?php

namespace App\Services;

use App\Models\PriceSubmission;
use App\Models\User;

class UserTrustScoreService
{
    /**
     * Computes the dynamic Bayesian trust score for a user.
     *
     * @return array{
     *     score: float,
     *     tier: string,
     *     can_auto_approve: bool,
     *     breakdown: array<string, float|int>
     * }
     */
    public function calculateTrust(User $user): array
    {
        $totalSubmissions = PriceSubmission::query()->where('user_id', $user->id)->count();
        $approvedCount = PriceSubmission::query()
            ->where('user_id', $user->id)
            ->where('status', PriceSubmission::STATUS_APPROVED)
            ->count();
        $rejectedCount = PriceSubmission::query()
            ->where('user_id', $user->id)
            ->where('status', PriceSubmission::STATUS_REJECTED)
            ->count();
        
        $geoverifiedCount = PriceSubmission::query()
            ->where('user_id', $user->id)
            ->where('is_geoverified', true)
            ->count();

        $reviewedTotal = $approvedCount + $rejectedCount;

        // 1. Approval Ratio (Default to 0.6 if brand new)
        $approvalRatio = $reviewedTotal > 0 ? ($approvedCount / $reviewedTotal) : 0.60;

        // 2. Geoverified Ratio
        $geoRatio = $totalSubmissions > 0 ? ($geoverifiedCount / $totalSubmissions) : 0.50;

        // 3. Streak Factor (0 to 1 based on 7-day goal)
        $streak = (int) ($user->submission_streak ?? 0);
        $streakFactor = min(1.0, $streak / 7.0);

        // 4. Volume Maturity Factor (0 to 1 based on 10 approved milestones)
        $volumeFactor = min(1.0, $approvedCount / 10.0);

        // Weighted Trust Formula
        $trustScore = (0.40 * $approvalRatio) +
                      (0.30 * $geoRatio) +
                      (0.15 * $streakFactor) +
                      (0.15 * $volumeFactor);

        // Admin/Moderators automatically receive maximum trust
        if ($user->isAdminOrModerator()) {
            $trustScore = 1.0;
        }

        $trustScore = round(max(0.10, min(1.00, $trustScore)), 2);

        $tier = match (true) {
            $trustScore >= 0.85 => 'Trusted Champion',
            $trustScore >= 0.70 => 'Verified Contributor',
            $trustScore >= 0.45 => 'Regular Reporter',
            default => 'Probationary',
        };

        // Auto approval requirement: score >= 0.70 AND at least 3 historical approved submissions (or admin)
        $canAutoApprove = $user->isAdminOrModerator() || ($trustScore >= 0.70 && $approvedCount >= 3);

        return [
            'score' => $trustScore,
            'tier' => $tier,
            'can_auto_approve' => $canAutoApprove,
            'breakdown' => [
                'approval_ratio' => round($approvalRatio, 2),
                'geo_ratio' => round($geoRatio, 2),
                'streak_factor' => round($streakFactor, 2),
                'volume_factor' => round($volumeFactor, 2),
                'approved_count' => $approvedCount,
                'rejected_count' => $rejectedCount,
                'geoverified_count' => $geoverifiedCount,
            ],
        ];
    }
}
