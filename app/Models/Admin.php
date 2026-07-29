<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MODERATOR = 'moderator';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_primary',
        'restricted_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_primary' => 'boolean',
            'restricted_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function isAdminOrModerator(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MODERATOR], true);
    }

    public function isPrimary(): bool
    {
        return (bool) $this->is_primary;
    }

    public function isRestricted(): bool
    {
        return $this->restricted_at !== null;
    }

    /** Full admins who are not restricted can manage the admin roster. */
    public function canManageAdmins(): bool
    {
        return $this->role === self::ROLE_ADMIN && ! $this->isRestricted();
    }
}
