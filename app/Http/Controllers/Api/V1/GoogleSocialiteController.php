<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use App\Models\User;
use App\Services\GoogleAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleSocialiteController extends Controller
{
    use ApiResponse;

    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback(Request $request, GoogleAuthService $google): RedirectResponse
    {
        $oauthUser = Socialite::driver('google')->stateless()->user();

        $existing = User::query()
            ->where('google_id', $oauthUser->getId())
            ->orWhere('email', strtolower((string) $oauthUser->getEmail()))
            ->first();

        $isNew = ! $existing;

        if (! $existing) {
            $existing = User::query()->create([
                'name' => $oauthUser->getName() ?: 'Market Eye User',
                'email' => strtolower((string) $oauthUser->getEmail()),
                'google_id' => $oauthUser->getId(),
                'avatar' => $oauthUser->getAvatar(),
                'password' => Hash::make(Str::random(32)),
                'role' => User::ROLE_USER,
                'points' => 0,
                'wallet_balance' => 0,
                'verified' => true,
            ]);
        } else {
            $existing->google_id = $existing->google_id ?: $oauthUser->getId();
            if ($oauthUser->getAvatar() && ! $existing->avatar) {
                $existing->avatar = $oauthUser->getAvatar();
            }
            $existing->verified = true;
            $existing->save();
        }

        if ($isNew) {
            try {
                Mail::to($existing->email)->send(new WelcomeMail($existing->name));
            } catch (\Throwable) {
            }
        }

        $token = $existing->createToken('web-google')->plainTextToken;
        $deepLink = 'marketeye://auth/google?token='.urlencode($token);

        return redirect()->away($deepLink);
    }
}
