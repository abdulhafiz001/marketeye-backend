<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Mail\WelcomeMail;
use App\Models\User;
use App\Services\GoogleAuthService;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'phone' => $request->input('phone'),
            'role' => User::ROLE_USER,
            'points' => 0,
            'wallet_balance' => 0,
            'verified' => false,
        ]);

        try {
            Mail::to($user->email)->send(new WelcomeMail($user->name));
        } catch (\Throwable) {
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->success([
            'user' => $this->userPayload($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Registered.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $login = trim((string) ($request->input('login') ?: $request->input('email')));
        $user = User::query()
            ->where('email', $login)
            ->orWhere('phone', $login)
            ->first();

        if (! $user || ! $user->password || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return $this->failure('Invalid credentials.', [], 401);
        }

        if ($user->isBanned()) {
            return $this->failure('Account suspended.', [], 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->success([
            'user' => $this->userPayload($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function google(Request $request, GoogleAuthService $google): JsonResponse
    {
        $data = $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        $user = $google->authenticateWithIdToken($data['id_token']);
        $token = $user->createToken('mobile-google')->plainTextToken;

        return $this->success([
            'user' => $this->userPayload($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Signed in with Google.');
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
