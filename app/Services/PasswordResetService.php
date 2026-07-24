<?php

namespace App\Services;

use App\Mail\PasswordOtpMail;
use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    public const OTP_TTL_MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    /**
     * @return array{sent: bool}
     */
    public function sendOtp(string $email): array
    {
        $email = strtolower(trim($email));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'No account found with this email address.',
            ]);
        }

        $code = (string) random_int(100000, 999999);

        PasswordOtp::query()->where('email', $email)->delete();
        PasswordOtp::query()->create([
            'email' => $email,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            'attempts' => 0,
        ]);

        Mail::to($email)->send(new PasswordOtpMail($code));

        return ['sent' => true];
    }

    public function verifyCode(string $email, string $code): bool
    {
        $email = strtolower(trim($email));

        $otp = PasswordOtp::query()
            ->where('email', $email)
            ->orderByDesc('id')
            ->first();

        if (! $otp || $otp->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => 'Invalid or expired code. Codes expire after '.self::OTP_TTL_MINUTES.' minutes.',
            ]);
        }

        if ((int) $otp->attempts >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'code' => 'Too many attempts. Request a new code.',
            ]);
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code)) {
            throw ValidationException::withMessages(['code' => 'Invalid or expired code.']);
        }

        return true;
    }

    public function resetPassword(string $email, string $code, string $password): User
    {
        $email = strtolower(trim($email));
        $this->verifyCode($email, $code);

        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            throw ValidationException::withMessages(['email' => 'Account not found.']);
        }

        $user->password = $password;
        $user->save();

        PasswordOtp::query()->where('email', $email)->delete();

        return $user;
    }
}
