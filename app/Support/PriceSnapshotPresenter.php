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
     * @return 'high'|'low'|'stale'
     */
    public static function confidenceLevel(PriceSnapshot $snapshot): string
    {
        if (self::isStale($snapshot)) {
            return 'stale';
        }

        if ($snapshot->low_confidence || (int) $snapshot->submission_count < 2) {
            return 'low';
        }

        if ((int) $snapshot->submission_count >= 3) {
            return 'high';
        }

        return 'low';
    }
}
