<?php

namespace App\Services;

use App\Models\PriceSubmission;
use App\Models\Product;

class AnomalyDetectionService
{
    /**
     * Statistical Outlier Detection using Interquartile Range (IQR) and Bounds Checking.
     *
     * @return array{
     *     is_outlier: bool,
     *     reason: ?string,
     *     lower_bound: ?float,
     *     upper_bound: ?float,
     *     median: ?float,
     *     sample_size: int,
     *     method: string
     * }
     */
    public function evaluateSubmission(float $pricePerUnit, int $productId, int $marketId): array
    {
        if ($pricePerUnit <= 0) {
            return [
                'is_outlier' => true,
                'reason' => 'Price per unit must be greater than zero.',
                'lower_bound' => 0.0,
                'upper_bound' => null,
                'median' => null,
                'sample_size' => 0,
                'method' => 'non_positive_check',
            ];
        }

        // 1. Fetch market-specific approved prices from the last 30 days
        $prices = PriceSubmission::query()
            ->where('product_id', $productId)
            ->where('market_id', $marketId)
            ->where('status', PriceSubmission::STATUS_APPROVED)
            ->where('submitted_at', '>=', now()->subDays(30))
            ->pluck('price_per_unit')
            ->map(fn ($p) => (float) $p)
            ->filter(fn ($p) => $p > 0)
            ->sort()
            ->values();

        $method = 'market_iqr';

        // 2. If market sample is small (< 5), fallback to city-wide (all markets) for this product
        if ($prices->count() < 5) {
            $cityPrices = PriceSubmission::query()
                ->where('product_id', $productId)
                ->where('status', PriceSubmission::STATUS_APPROVED)
                ->where('submitted_at', '>=', now()->subDays(45))
                ->pluck('price_per_unit')
                ->map(fn ($p) => (float) $p)
                ->filter(fn ($p) => $p > 0)
                ->sort()
                ->values();

            if ($cityPrices->count() >= 5) {
                $prices = $cityPrices;
                $method = 'city_wide_iqr';
            }
        }

        $sampleSize = $prices->count();

        // If still under 4 samples, apply baseline threshold multiplier based on any available records or product seed
        if ($sampleSize < 4) {
            if ($sampleSize > 0) {
                $median = (float) $prices->median();
                $lower = round($median * 0.25, 2);
                $upper = round($median * 3.5, 2);

                $isOutlier = ($pricePerUnit < $lower || $pricePerUnit > $upper);
                $reason = $isOutlier
                    ? sprintf('Price ₦%s deviates significantly from preliminary median ₦%s (expected between ₦%s and ₦%s).', number_format($pricePerUnit, 2), number_format($median, 2), number_format($lower, 2), number_format($upper, 2))
                    : null;

                return [
                    'is_outlier' => $isOutlier,
                    'reason' => $reason,
                    'lower_bound' => $lower,
                    'upper_bound' => $upper,
                    'median' => $median,
                    'sample_size' => $sampleSize,
                    'method' => 'heuristic_deviation',
                ];
            }

            return [
                'is_outlier' => false,
                'reason' => null,
                'lower_bound' => null,
                'upper_bound' => null,
                'median' => null,
                'sample_size' => 0,
                'method' => 'cold_start_passed',
            ];
        }

        // 3. IQR Calculation
        $q1Index = (int) floor($sampleSize * 0.25);
        $q3Index = (int) min(floor($sampleSize * 0.75), $sampleSize - 1);
        $q1 = (float) $prices->get($q1Index);
        $q3 = (float) $prices->get($q3Index);
        $iqr = $q3 - $q1;
        $median = (float) $prices->median();

        // Standard 1.5 * IQR rule for outlier detection
        $lowerBound = max(0.0, round($q1 - (1.5 * $iqr), 2));
        $upperBound = round($q3 + (1.5 * $iqr), 2);

        // Ensure reasonable minimum bounds if variance was very tight
        if ($lowerBound == $upperBound || $iqr == 0) {
            $lowerBound = max(0.0, round($median * 0.4, 2));
            $upperBound = round($median * 2.5, 2);
        }

        $isOutlier = ($pricePerUnit < $lowerBound || $pricePerUnit > $upperBound);

        $reason = null;
        if ($isOutlier) {
            $reason = sprintf(
                'Price ₦%s is outside statistical tolerance bounds (₦%s - ₦%s, Median: ₦%s, IQR: ₦%s).',
                number_format($pricePerUnit, 2),
                number_format($lowerBound, 2),
                number_format($upperBound, 2),
                number_format($median, 2),
                number_format($iqr, 2)
            );
        }

        return [
            'is_outlier' => $isOutlier,
            'reason' => $reason,
            'lower_bound' => $lowerBound,
            'upper_bound' => $upperBound,
            'median' => $median,
            'sample_size' => $sampleSize,
            'method' => $method,
        ];
    }
}
