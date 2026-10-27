<?php

namespace App\Services;

use App\Models\Market;
use Illuminate\Validation\ValidationException;

class MarketProximityService
{
    public const MAX_DISTANCE_KM = 0.75;

    public function assertWithinMarket(Market $market, mixed $latitude, mixed $longitude): float
    {
        if ($latitude === null || $longitude === null) {
            throw ValidationException::withMessages([
                'location' => ["Allow location access and stand near {$market->name} to submit prices for it."],
            ]);
        }

        if ($market->latitude === null || $market->longitude === null) {
            throw ValidationException::withMessages([
                'market_id' => ["{$market->name} has no verified map location, so prices cannot be submitted for it yet."],
            ]);
        }

        $distance = $this->distanceKm((float) $latitude, (float) $longitude, (float) $market->latitude, (float) $market->longitude);
        if ($distance > self::MAX_DISTANCE_KM) {
            $distanceLabel = $distance < 10 ? number_format($distance, 1) : number_format($distance, 0);
            throw ValidationException::withMessages([
                'market_id' => ["You are {$distanceLabel} km from {$market->name}. You must be within 750 m of this market to submit a price for it."],
            ]);
        }

        return $distance;
    }

    public function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
