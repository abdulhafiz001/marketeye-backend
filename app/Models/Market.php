<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Market extends Model
{
    public const DEFAULT_GEOFENCE_RADIUS_M = 750;

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

        return max(100, min(5000, $meters));
    }

    public function geofenceRadiusKm(): float
    {
        return $this->geofenceRadiusMeters() / 1000;
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
