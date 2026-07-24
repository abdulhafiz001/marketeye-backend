<?php

namespace App\Services;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleAuthService
{
    public function authenticateWithIdToken(string $idToken): User
    {
        $payload = $this->verifyIdToken($idToken);

        $googleId = (string) ($payload['sub'] ?? '');
        $email = strtolower((string) ($payload['email'] ?? ''));
        $name = (string) ($payload['name'] ?? 'Market Eye User');
        $avatar = $payload['picture'] ?? null;

        if ($googleId === '' || $email === '') {
            throw ValidationException::withMessages(['id_token' => 'Invalid Google token.']);
        }

        $user = User::query()->where('google_id', $googleId)->first();

        if (! $user) {
            $user = User::query()->where('email', $email)->first();
        }

        $isNew = false;

        if (! $user) {
            $isNew = true;
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'avatar' => $avatar,
                'password' => Hash::make(Str::random(32)),
                'role' => User::ROLE_USER,
                'points' => 0,
                'wallet_balance' => 0,
                'verified' => true,
            ]);
        } else {
            $user->google_id = $user->google_id ?: $googleId;
            if ($avatar && ! $user->avatar) {
                $user->avatar = $avatar;
            }
            if (! $user->verified) {
                $user->verified = true;
            }
            $user->save();
        }

        if ($user->isBanned()) {
            throw ValidationException::withMessages(['id_token' => 'Account suspended.']);
        }

        if ($isNew) {
            try {
                Mail::to($user->email)->send(new WelcomeMail($user->name));
            } catch (\Throwable) {
            }
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function verifyIdToken(string $idToken): array
    {
        $response = Http::timeout(15)->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if (! $response->successful()) {
            throw ValidationException::withMessages(['id_token' => 'Could not verify Google token.']);
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw ValidationException::withMessages(['id_token' => 'Invalid Google token payload.']);
        }

        $aud = (string) ($payload['aud'] ?? '');
        $allowed = array_filter([
            config('services.google.client_id'),
            env('GOOGLE_ANDROID_CLIENT_ID'),
            env('GOOGLE_IOS_CLIENT_ID'),
        ]);

        if ($allowed && ! in_array($aud, $allowed, true)) {
            throw ValidationException::withMessages(['id_token' => 'Google token audience mismatch.']);
        }

        if (($payload['email_verified'] ?? 'true') === 'false' || ($payload['email_verified'] ?? true) === false) {
            throw ValidationException::withMessages(['id_token' => 'Google email is not verified.']);
        }

        return $payload;
    }
}
