<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Developer extends Authenticatable
{
    use Notifiable;

    /** Max active API keys a developer may hold */
    public const MAX_ACTIVE_KEYS = 3;

    /** Default daily request limit for new keys */
    public const DEFAULT_DAILY_LIMIT = 200;

    /** Max daily limit a developer can choose without admin approval */
    public const MAX_SELF_SERVE_DAILY_LIMIT = 500;

    protected $fillable = [
        'name',
        'email',
        'password',
        'organization',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }
}
