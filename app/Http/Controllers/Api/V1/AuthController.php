<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Mail\WelcomeMail;
use App\Models\User;
use App\Services\EmailVerificationService;
use App\Services\PasswordResetService;
use App\Support\AuthValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request, EmailVerificationService $verification): JsonResponse
    {
        $email = $request->string('email')->toString();
        $existing = User::query()->where('email', $email)->first();

        if ($existing?->isEmailVerified()) {
            return $this->failure('An account with this email already exists.', [
                'email' => ['An account with this email already exists.'],
            ], 422);
        }

        $user = $existing ?? User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $email,
            'password' => $request->string('password')->toString(),
            'phone' => $request->input('phone'),
            'role' => User::ROLE_USER,
            'points' => 0,
            'wallet_balance' => 0,
            'verified' => false,
            'email_verified_at' => null,
        ]);

        if ($existing) {
            $user->fill([
                'name' => $request->string('name')->toString(),
                'password' => $request->string('password')->toString(),
                'phone' => $request->input('phone'),
            ])->save();
        }

        try {
            $verification->sendOtp($user);
        } catch (\Throwable $e) {
            report($e);
        }

        return $this->success([
            'requires_verification' => true,
            'email' => $user->email,
            'expires_in_minutes' => EmailVerificationService::OTP_TTL_MINUTES,
        ], 'We sent a 6-digit code to your email. It expires in '.EmailVerificationService::OTP_TTL_MINUTES.' minutes.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $login = trim((string) ($request->input('login') ?: $request->input('email')));
        $user = str_contains($login, '@')
            ? User::query()->where('email', AuthValidation::normalizeEmail($login))->first()
            : User::query()->where('phone', AuthValidation::normalizePhone($login) ?: $login)->first();

        if (! $user || ! $user->password || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return $this->failure('Invalid credentials.', [], 401);
        }

        if ($user->isBanned()) {
            return $this->failure('Account suspended.', [], 403);
        }

        if (! $user->isEmailVerified()) {
            try {
                app(EmailVerificationService::class)->sendOtp($user);
            } catch (\Throwable $e) {
                report($e);
            }

            return $this->failure('Verify your email to continue.', [
                'code' => ['email_unverified'],
                'email' => [$user->email],
            ], 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->success([
            'user' => $this->userPayload($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function verifyEmail(Request $request, EmailVerificationService $verification): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $verification->verify($data['email'], $data['code']);

        $welcomeName = $user->name ?: (string) str($user->email)->before('@');
        try {
            Mail::to($user->email)->send(new WelcomeMail($welcomeName));
        } catch (\Throwable) {
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->success([
            'user' => $this->userPayload($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Email verified.');
    }

    public function resendVerification(Request $request, EmailVerificationService $verification): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', strtolower(trim($data['email'])))->first();
        if (! $user) {
            return $this->failure('No account found with this email address.', [
                'email' => ['No account found with this email address.'],
            ], 422);
        }

        $verification->sendOtp($user);

        return $this->success(
            ['expires_in_minutes' => EmailVerificationService::OTP_TTL_MINUTES],
            'A new 6-digit code has been sent to your email. It expires in '.EmailVerificationService::OTP_TTL_MINUTES.' minutes.'
        );
    }

    public function forgotPassword(Request $request, PasswordResetService $reset): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $reset->sendOtp($data['email']);

        return $this->success(
            ['expires_in_minutes' => PasswordResetService::OTP_TTL_MINUTES],
            'A 6-digit code has been sent to your email. It expires in '.PasswordResetService::OTP_TTL_MINUTES.' minutes.'
        );
    }

    public function verifyResetCode(Request $request, PasswordResetService $reset): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $reset->verifyCode($data['email'], $data['code']);

        return $this->success(['valid' => true], 'Code verified.');
    }

    public function resetPassword(Request $request, PasswordResetService $reset): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $reset->resetPassword($data['email'], $data['code'], $data['password']);

        return $this->success([
            'user' => $this->userPayload($user),
        ], 'Password updated. You can sign in now.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'phone' => $request->input('phone'),
        ]);
        $user->save();

        return $this->success([
            'user' => $this->userPayload($user),
        ], 'Profile updated.');
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'role' => $user->role,
            'points' => (int) $user->points,
            'wallet_balance' => (int) $user->wallet_balance,
            'submission_streak' => (int) ($user->submission_streak ?? 0),
            'verified' => (bool) $user->verified,
        ];
    }
}
