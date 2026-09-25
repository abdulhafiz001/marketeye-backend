<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordOtp extends Model
{
    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    public const PURPOSE_EMAIL_VERIFY = 'email_verify';

    protected $fillable = [
        'email',
        'purpose',
        'code',
        'expires_at',
        'attempts',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
