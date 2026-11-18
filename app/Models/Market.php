<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Market extends Model
{
    public const DEFAULT_GEOFENCE_RADIUS_M = 10000;

    public const MAX_GEOFENCE_RADIUS_M = 100000;

    protected $fillable = [
        'name',
        'area',
        'city',
        'state',
        'latitude',
        'longitude',
        'geofence_radius_m',
        'description',
        'image',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'geofence_radius_m' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function geofenceRadiusMeters(): int
    {
        $meters = (int) ($this->geofence_radius_m ?: self::DEFAULT_GEOFENCE_RADIUS_M);

        return max(100, min(self::MAX_GEOFENCE_RADIUS_M, $meters));
    }

    public function geofenceRadiusKm(): float
    {
        return $this->geofenceRadiusMeters() / 1000;
    }

    public function geofenceRadiusLabel(): string
    {
        $meters = $this->geofenceRadiusMeters();
        if ($meters >= 1000) {
            $km = rtrim(rtrim(number_format($meters / 1000, 1), '0'), '.');

            return "{$km} km";
        }

        return "{$meters} m";
    }

    public function hasVerifiedLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function priceSubmissions(): HasMany
    {
        return $this->hasMany(PriceSubmission::class);
    }

    public function priceSnapshots(): HasMany
    {
        return $this->hasMany(PriceSnapshot::class);
    }
}
