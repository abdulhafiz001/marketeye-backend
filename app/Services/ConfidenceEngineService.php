<?php

namespace App\Services;

use App\Models\PriceConfirmation;
use App\Models\PriceSnapshot;
use App\Models\PriceSubmission;
use Carbon\Carbon;

class ConfidenceEngineService
{
    /**
     * Evaluates continuous multi-signal confidence score for a commodity at a market.
     *
     * @return array{
     *     score: int,
     *     level: string,
     *     confirmations_count: int,
     *     disputes_count: int,
     *     observations_count: int,
     *     observed_range: array{min: float, max: float, typical: float},
     *     last_observed_at: ?string,
     *     last_observed_ago: string,
     *     signals: array<string, mixed>,
     *     verdict: string
     * }
     */
    public function evaluate(int $productId, int $marketId, ?PriceSnapshot $snapshot = null): array
    {
        $now = now();
        $recentWindow = $now->copy()->subDays(7);

        // 1. Fetch recent approved submissions (last 7 days)
        $submissions = PriceSubmission::query()
            ->with('user')
            ->where('product_id', $productId)
            ->where('market_id', $marketId)
            ->where('status', PriceSubmission::STATUS_APPROVED)
            ->where('submitted_at', '>=', $recentWindow)
            ->orderByDesc('submitted_at')
            ->get();

        // 2. Fetch community confirmations and disputes (last 14 days)
        $confirmations = PriceConfirmation::query()
            ->where('product_id', $productId)
            ->where('market_id', $marketId)
            ->where('created_at', '>=', $now->copy()->subDays(14))
            ->get();

        $confirmCount = $confirmations->where('action', PriceConfirmation::ACTION_CONFIRM)->count();
        $disputeCount = $confirmations->where('action', PriceConfirmation::ACTION_DISPUTE)->count();

        $obsCount = $submissions->count();
        $latestSubmission = $submissions->first();
        $lastObservedAt = $latestSubmission?->submitted_at;

        // Base score starts at 50 (neutral provisional confidence)
        $score = 50;

        // Signal 1: Observation Recency & Volume (Max +30, or penalty for age)
        $recencyScore = 0;
        if ($obsCount > 0) {
            $hoursOld = $lastObservedAt ? $lastObservedAt->diffInHours($now) : 1000;
            if ($hoursOld <= 12) {
                $recencyScore += 20;
            } elseif ($hoursOld <= 24) {
                $recencyScore += 15;
            } elseif ($hoursOld <= 48) {
                $recencyScore += 10;
            } elseif ($hoursOld <= 72) {
                $recencyScore += 5;
            } else {
                $recencyScore -= 15; // Age decay penalty
            }

            // Volume bonus
            $recencyScore += min(10, $obsCount * 3);
        } else {
            $recencyScore -= 20; // No recent observations
        }
        $score += $recencyScore;

        // Signal 2: Community Confirmations vs Disputes (Max +25, or heavy dispute penalty)
        $communityScore = 0;
        $communityScore += ($confirmCount * 5); // +5 per confirm
        $communityScore -= ($disputeCount * 15); // -15 per dispute (disputes are strong negative signals)
        if ($confirmCount >= 3 && $disputeCount === 0) {
            $communityScore += 5; // Unanimous consensus bonus
        }
        $communityScore = max(-35, min(25, $communityScore));
        $score += $communityScore;

        // Signal 3: Contributor Reliability / Bayesian Reputation (Max +20)
        $contributorScore = 0;
        if ($obsCount > 0) {
            $trustService = app(UserTrustScoreService::class);
            $totalTrust = 0.0;
            $contributorCount = 0;
            foreach ($submissions as $sub) {
                if ($sub->user) {
                    $trust = $trustService->calculateTrust($sub->user)['score'];
                    $totalTrust += $trust;
                    $contributorCount++;
                }
            }
            $avgTrust = $contributorCount > 0 ? ($totalTrust / $contributorCount) : 0.6;
            $contributorScore = (int) round($avgTrust * 20);
        }
        $score += $contributorScore;

        // Signal 4: GPS On-Site Geoverification Proof (Max +15)
        $geoScore = 0;
        $geoCount = $submissions->where('is_geoverified', true)->count() +
                    $confirmations->where('is_geoverified', true)->count();
        if ($geoCount >= 2) {
            $geoScore = 15;
        } elseif ($geoCount === 1) {
            $geoScore = 10;
        }
        $score += $geoScore;

        // Signal 5: Statistical Dispersion / Price Spread Stability (Max +10)
        $dispersionScore = 0;
        $prices = $submissions->pluck('price_per_unit')->map(fn ($p) => (float) $p)->filter(fn ($p) => $p > 0);
        if ($prices->count() >= 2) {
            $minP = (float) $prices->min();
            $maxP = (float) $prices->max();
            $avgP = (float) $prices->avg();
            $spreadRatio = $avgP > 0 ? (($maxP - $minP) / $avgP) : 0;
            if ($spreadRatio <= 0.10) {
                $dispersionScore = 10; // Very tight market consensus
            } elseif ($spreadRatio <= 0.25) {
                $dispersionScore = 5;
            }
        } elseif ($prices->count() === 1) {
            $dispersionScore = 5;
        }
        $score += $dispersionScore;

        // Clamp final score between 10 and 100
        $finalScore = (int) max(10, min(100, $score));

        // Determine Level
        $isStale = $lastObservedAt ? $lastObservedAt->diffInDays($now) >= 4 : true;
        $level = match (true) {
            $disputeCount >= 2 && $disputeCount >= $confirmCount => 'needs_review',
            $isStale => 'stale',
            $finalScore >= 80 => 'high',
            $finalScore >= 60 => 'medium',
            $finalScore >= 40 => 'low',
            default => 'needs_review',
        };

        // Price range calculations
        $typicalPrice = $snapshot ? (float) $snapshot->avg_price : ($prices->avg() ?: 0.0);
        $minPrice = $snapshot && (float) $snapshot->min_price > 0 ? (float) $snapshot->min_price : ($prices->min() ?: $typicalPrice);
        $maxPrice = $snapshot && (float) $snapshot->max_price > 0 ? (float) $snapshot->max_price : ($prices->max() ?: $typicalPrice);

        // Formatting relative time
        $ago = $lastObservedAt ? $lastObservedAt->diffForHumans(['parts' => 1, 'short' => false]) : 'Awaiting recent reports';

        $verdict = match ($level) {
            'high' => "High Confidence ({$finalScore}%) • Strongly verified by {$obsCount} reports & {$confirmCount} confirmations",
            'medium' => "Moderate Confidence ({$finalScore}%) • {$obsCount} recent observations",
            'low' => "Provisional ({$finalScore}%) • Needs more community confirmations",
            'needs_review' => "Disputed ({$finalScore}%) • Conflicting prices reported",
            'stale' => "Stale ({$finalScore}%) • Last seen {$ago}",
            default => "{$finalScore}% Confidence",
        };

        return [
            'score' => $finalScore,
            'level' => $level,
            'confirmations_count' => $confirmCount,
            'disputes_count' => $disputeCount,
            'observations_count' => $obsCount,
            'observed_range' => [
                'min' => round((float) $minPrice, 2),
                'max' => round((float) $maxPrice, 2),
                'typical' => round((float) $typicalPrice, 2),
            ],
            'last_observed_at' => $lastObservedAt?->toIso8601String(),
            'last_observed_ago' => $ago,
            'signals' => [
                'recency_score' => $recencyScore,
                'community_score' => $communityScore,
                'contributor_score' => $contributorScore,
                'geo_score' => $geoScore,
                'dispersion_score' => $dispersionScore,
                'is_geoverified' => $geoCount > 0,
            ],
            'verdict' => $verdict,
        ];
    }
}
