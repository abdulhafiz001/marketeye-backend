<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Developer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DeveloperPortalController extends Controller
{
    public function showRegister(): View
    {
        return view('developer.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:developers,email'],
            'organization' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $developer = Developer::query()->create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'organization' => $data['organization'] ?? null,
            'password' => $data['password'],
            'is_active' => true,
        ]);

        Auth::guard('developer')->login($developer);

        return redirect()->route('developer.dashboard');
    }

    public function showLogin(): View
    {
        return view('developer.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $developer = Developer::query()->where('email', strtolower($data['email']))->first();
        if (! $developer || ! Hash::check($data['password'], $developer->password)) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
        }

        if (! $developer->is_active) {
            return back()->withErrors(['email' => 'This developer account is disabled.'])->withInput();
        }

        Auth::guard('developer')->login($developer);

        return redirect()->route('developer.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('developer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('developer.login');
    }

    public function dashboard(): View
    {
        /** @var Developer $developer */
        $developer = Auth::guard('developer')->user();

        return view('developer.dashboard', [
            'developer' => $developer,
            'keys' => $developer->apiKeys()->orderByDesc('created_at')->get(),
            'maxKeys' => Developer::MAX_ACTIVE_KEYS,
            'defaultLimit' => Developer::DEFAULT_DAILY_LIMIT,
            'maxSelfLimit' => Developer::MAX_SELF_SERVE_DAILY_LIMIT,
        ]);
    }

    public function storeKey(Request $request)
    {
        /** @var Developer $developer */
        $developer = Auth::guard('developer')->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'daily_limit' => ['nullable', 'integer', 'min:10', 'max:'.Developer::MAX_SELF_SERVE_DAILY_LIMIT],
        ]);

        $activeCount = $developer->apiKeys()->where('is_active', true)->count();
        if ($activeCount >= Developer::MAX_ACTIVE_KEYS) {
            return back()->withErrors([
                'name' => 'You can have at most '.Developer::MAX_ACTIVE_KEYS.' active API keys. Revoke one first or ask an admin.',
            ]);
        }

        $limit = (int) ($data['daily_limit'] ?? Developer::DEFAULT_DAILY_LIMIT);
        $limit = min($limit, Developer::MAX_SELF_SERVE_DAILY_LIMIT);

        $issued = ApiKey::issueForDeveloper($developer, $data['name'], $limit);

        return back()
            ->with('status', 'API key created. Copy it now — it will not be shown again.')
            ->with('new_api_key', $issued['plain']);
    }

    public function revokeKey(int $id)
    {
        /** @var Developer $developer */
        $developer = Auth::guard('developer')->user();

        $key = $developer->apiKeys()->whereKey($id)->firstOrFail();
        $key->update(['is_active' => false]);

        return back()->with('status', 'API key revoked.');
    }
}
