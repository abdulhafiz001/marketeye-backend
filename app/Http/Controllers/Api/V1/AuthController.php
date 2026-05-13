<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
            'verified' => false,
        ]);

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

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
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
            'verified' => (bool) $user->verified,
        ];
    }
}
