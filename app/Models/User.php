<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_USER = 'user';

    public const ROLE_MODERATOR = 'moderator';

    public const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'expo_push_token',
        'fcm_device_token',
        'avatar',
        'role',
        'points',
        'wallet_balance',
        'google_id',
        'verified',
        'banned_at',
        'submission_streak',
        'last_submission_date',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'verified' => 'boolean',
            'banned_at' => 'datetime',
            'last_submission_date' => 'date',
            'wallet_balance' => 'integer',
        ];
    }

    public function priceSubmissions(): HasMany
    {
        return $this->hasMany(PriceSubmission::class);
    }

    public function marketWatches(): HasMany
    {
        return $this->hasMany(UserMarketWatch::class);
    }

    public function airtimeClaims(): HasMany
    {
        return $this->hasMany(AirtimeClaim::class);
    }

    public function priceAlerts(): HasMany
    {
        return $this->hasMany(PriceAlert::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function isAdminOrModerator(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MODERATOR], true);
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }
}
