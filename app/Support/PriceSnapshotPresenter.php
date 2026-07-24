<?php

namespace App\Support;

use App\Models\PriceSnapshot;
use Carbon\Carbon;

class PriceSnapshotPresenter
{
    public static function isStale(PriceSnapshot $snapshot): bool
    {
        $days = Carbon::today()->diffInDays(Carbon::parse($snapshot->snapshot_date)->startOfDay());

        return $days >= 3;
    }

    /**
     * @return 'high'|'medium'|'low'|'stale'
     */
    public static function confidenceLevel(PriceSnapshot $snapshot): string
    {
        if (self::isStale($snapshot)) {
            return 'stale';
        }

        // Admin manual prices are trusted.
        if ($snapshot->snapshot_source === PriceSnapshot::SOURCE_MANUAL && ! $snapshot->low_confidence) {
            return 'high';
        }

        if ($snapshot->low_confidence) {
            return 'low';
        }

        $count = (int) $snapshot->submission_count;

        if ($count >= 3) {
            return 'high';
        }

        if ($count >= 1) {
            return 'medium';
        }

        return 'low';
    }
}
