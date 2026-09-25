<?php

namespace App\Services;

use App\Mail\EmailVerificationOtpMail;
use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class EmailVerificationService
{
    public const OTP_TTL_MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    public function sendOtp(User $user): void
    {
        if ($user->isEmailVerified()) {
            throw ValidationException::withMessages([
                'email' => 'This email is already verified. You can sign in.',
            ]);
        }

        $email = strtolower(trim((string) $user->email));
        $code = (string) random_int(100000, 999999);

        PasswordOtp::query()
            ->where('email', $email)
            ->where('purpose', PasswordOtp::PURPOSE_EMAIL_VERIFY)
            ->delete();

        PasswordOtp::query()->create([
            'email' => $email,
            'purpose' => PasswordOtp::PURPOSE_EMAIL_VERIFY,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            'attempts' => 0,
        ]);

        Mail::to($email)->send(new EmailVerificationOtpMail($code, (string) ($user->name ?: '')));
    }

    public function verify(string $email, string $code): User
    {
        $email = strtolower(trim($email));

        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'No account found with this email address.',
            ]);
        }

        if ($user->isEmailVerified()) {
            return $user;
        }

        $otp = PasswordOtp::query()
            ->where('email', $email)
            ->where('purpose', PasswordOtp::PURPOSE_EMAIL_VERIFY)
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

        $user->markEmailVerified();
        PasswordOtp::query()
            ->where('email', $email)
            ->where('purpose', PasswordOtp::PURPOSE_EMAIL_VERIFY)
            ->delete();

        return $user->fresh();
    }
}
