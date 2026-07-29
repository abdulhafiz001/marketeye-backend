<?php

namespace App\Http\Controllers\AdminWeb;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminActivityLog;
use App\Models\AirtimeClaim;
use App\Models\ApiKey;
use App\Models\Category;
use App\Models\ExternalPriceSeed;
use App\Models\Market;
use App\Models\PriceSnapshot;
use App\Models\PriceSubmission;
use App\Models\Product;
use App\Models\User;
use App\Services\AdminActivityLogger;
use App\Services\AnalyticsService;
use App\Services\ExternalDataSeedService;
use App\Services\GamificationService;
use App\Services\PriceSnapshotService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminWebController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.login');
    }

    public function showSetup()
    {
        if (Admin::query()->exists()) {
            return redirect()->route('admin.login');
        }

        return view('admin.setup');
    }

    public function setup(Request $request)
    {
        if (Admin::query()->exists()) {
            return redirect()->route('admin.login');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $admin = Admin::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => Admin::ROLE_ADMIN,
        ]);

        Auth::guard('admin')->login($admin);
        $admin->forceFill(['last_login_at' => now()])->save();

        return redirect('/admin');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = Admin::query()->where('email', $data['email'])->first();
        if (! $admin || ! Hash::check($data['password'], $admin->password)) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
        }

        if (! $admin->isAdminOrModerator()) {
            return back()->withErrors(['email' => 'Not authorized.'])->withInput();
        }

        Auth::guard('admin')->login($admin);
        $admin->forceFill(['last_login_at' => now()])->save();

        return redirect('/admin');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function dashboard(): View
    {
        $today = Carbon::today();

        $submissionsToday = PriceSubmission::query()
            ->whereDate('submitted_at', $today)
            ->count();

        $pending = PriceSubmission::query()
            ->where('status', PriceSubmission::STATUS_PENDING)
            ->count();

        $weekAgo = $today->copy()->subDays(7);
        $avgThisWeek = (float) (PriceSnapshot::query()
            ->where('snapshot_date', '>=', $weekAgo)
            ->avg('avg_price') ?? 0);

        $avgPrevWindow = (float) (PriceSnapshot::query()
            ->whereBetween('snapshot_date', [$weekAgo->copy()->subDays(7), $weekAgo])
            ->avg('avg_price') ?? 0);

        $pctChange = $avgPrevWindow > 0
            ? round((($avgThisWeek - $avgPrevWindow) / $avgPrevWindow) * 100, 2)
            : 0.0;

        $submissionsPerDay = PriceSubmission::query()
            ->selectRaw('DATE(submitted_at) as d, COUNT(*) as c')
            ->where('submitted_at', '>=', now()->subDays(30))
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $categoryChanges = DB::table('price_snapshots as ps')
            ->join('products as p', 'p.id', '=', 'ps.product_id')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->where('ps.snapshot_date', '>=', $weekAgo)
            ->selectRaw('c.name as category, AVG(ps.avg_price) as avg_price')
            ->groupBy('c.id', 'c.name')
            ->orderBy('c.name')
            ->get();

        $recentPending = PriceSubmission::query()
            ->with(['product', 'market', 'user'])
            ->where('status', PriceSubmission::STATUS_PENDING)
            ->orderByDesc('submitted_at')
            ->limit(10)
            ->get();

        $submissionsChart = [
            'labels' => $submissionsPerDay->pluck('d')->map(fn ($d) => (string) $d)->all(),
            'values' => $submissionsPerDay->pluck('c')->map(fn ($c) => (int) $c)->all(),
        ];

        $categoryChart = [
            'labels' => $categoryChanges->pluck('category')->map(fn ($c) => (string) $c)->all(),
            'values' => $categoryChanges
                ->map(fn ($r) => round((float) $r->avg_price, 2))
                ->values()
                ->all(),
        ];

        return view('admin.dashboard', [
            'stats' => [
                'trader_users' => User::query()->where('role', User::ROLE_USER)->count(),
                'submissions_today' => $submissionsToday,
                'pending_approvals' => $pending,
                'active_markets' => Market::query()->where('is_active', true)->count(),
                'products_count' => Product::query()->where('is_active', true)->count(),
                'price_change_percent_this_week' => $pctChange,
            ],
            'submissionsChart' => $submissionsChart,
            'categoryChart' => $categoryChart,
            'recentPending' => $recentPending,
        ]);
    }

    public function products(): View
    {
        return view('admin.products', [
            'products' => Product::query()->with('category')->orderBy('name')->limit(500)->get(),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function categories(): View
    {
        return view('admin.categories', [
            'categories' => Category::query()
                ->withCount('products')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeCategory(Request $request, AdminActivityLogger $logger)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:64'],
        ]);

        $category = Category::query()->create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'icon' => $data['icon'] ?? null,
        ]);

        $logger->log(Auth::guard('admin')->user(), 'Created category', Category::class, $category->id);

        return back()->with('status', 'Category created.');
    }

    public function updateCategory(int $id, Request $request, AdminActivityLogger $logger)
    {
        $category = Category::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:64'],
        ]);

        $category->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'icon' => $data['icon'] ?? $category->icon,
        ]);

        $logger->log(Auth::guard('admin')->user(), 'Updated category', Category::class, $category->id);

        return back()->with('status', 'Category updated.');
    }

    public function destroyCategory(int $id, AdminActivityLogger $logger)
    {
        $category = Category::query()->findOrFail($id);

        if ($category->products()->exists()) {
            return back()->withErrors(['category' => 'Cannot delete a category that still has products. Move or delete those products first.']);
        }

        $categoryId = $category->id;
        $category->delete();
        $logger->log(Auth::guard('admin')->user(), 'Deleted category', Category::class, $categoryId);

        return back()->with('status', 'Category deleted.');
    }

    public function markets(): View
    {
        return view('admin.markets', [
            'markets' => Market::query()->orderBy('name')->get(),
        ]);
    }

    public function users(): View
    {
        return view('admin.users', [
            'users' => User::query()->orderByDesc('id')->limit(300)->get(),
        ]);
    }

    public function submissions(): View
    {
        return view('admin.submissions', [
            'submissions' => PriceSubmission::query()
                ->with(['product', 'market', 'user'])
                ->orderByDesc('submitted_at')
                ->limit(150)
                ->get(),
        ]);
    }

    public function prices(): View
    {
        return view('admin.prices', [
            'snapshots' => PriceSnapshot::query()
                ->with(['product', 'market'])
                ->orderByDesc('snapshot_date')
                ->orderBy('product_id')
                ->limit(250)
                ->get(),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
            'markets' => Market::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function reports(): View
    {
        $today = now()->toDateString();
        $weekAgo = now()->subDays(7)->toDateString();
        $previousWeekStart = now()->subDays(14)->toDateString();

        $current = PriceSnapshot::query()
            ->select('product_id', DB::raw('AVG(avg_price) as current_avg'))
            ->whereBetween('snapshot_date', [$weekAgo, $today])
            ->groupBy('product_id');

        $previous = PriceSnapshot::query()
            ->select('product_id', DB::raw('AVG(avg_price) as previous_avg'))
            ->whereBetween('snapshot_date', [$previousWeekStart, $weekAgo])
            ->groupBy('product_id');

        $weeklyMovers = Product::query()
            ->leftJoinSub($current, 'current_prices', function ($join) {
                $join->on('products.id', '=', 'current_prices.product_id');
            })
            ->leftJoinSub($previous, 'previous_prices', function ($join) {
                $join->on('products.id', '=', 'previous_prices.product_id');
            })
            ->select([
                'products.id',
                'products.name',
                'products.unit',
                DB::raw('COALESCE(current_prices.current_avg, 0) as current_avg'),
                DB::raw('COALESCE(previous_prices.previous_avg, 0) as previous_avg'),
            ])
            ->where(function ($query) {
                $query->whereNotNull('current_prices.current_avg')
                    ->orWhereNotNull('previous_prices.previous_avg');
            })
            ->get()
            ->map(function ($row) {
                $currentAvg = (float) $row->current_avg;
                $previousAvg = (float) $row->previous_avg;
                $change = $previousAvg > 0 ? (($currentAvg - $previousAvg) / $previousAvg) * 100 : 0;

                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'unit' => $row->unit,
                    'current_avg' => round($currentAvg, 2),
                    'previous_avg' => round($previousAvg, 2),
                    'change_percent' => round($change, 2),
                ];
            })
            ->sortByDesc('change_percent')
            ->values();

        return view('admin.reports', [
            'weeklyMovers' => $weeklyMovers,
            'biggestIncrease' => $weeklyMovers->first(),
        ]);
    }

    public function analytics(AnalyticsService $analytics): View
    {
        return view('admin.analytics', $analytics->dashboard());
    }

    public function activity(): View
    {
        return view('admin.activity', [
            'activity' => AdminActivityLog::query()
                ->with('admin')
                ->orderByDesc('created_at')
                ->limit(100)
                ->get(),
        ]);
    }

    public function externalData(): View
    {
        return view('admin.external-data', [
            'seeds' => ExternalPriceSeed::query()
                ->with(['product', 'market', 'approver'])
                ->orderByDesc('created_at')
                ->limit(200)
                ->get(),
        ]);
    }

    public function pullExternalData(Request $request, ExternalDataSeedService $service)
    {
        try {
            $result = $service->runStaleSnapshotReview(Auth::guard('admin')->user());
        } catch (\Throwable $e) {
            return back()->withErrors(['external' => $e->getMessage()]);
        }

        if (($result['status'] ?? '') === 'failed') {
            return back()->withErrors(['external' => ($result['source'] ?? 'seed').': '.($result['message'] ?? 'Failed')]);
        }

        $count = (int) ($result['records_imported'] ?? 0);

        return back()->with('status', "Created review items for stale prices. {$count} item(s) are ready for review.");
    }

    public function storePrice(Request $request, PriceSnapshotService $snapshots, AdminActivityLogger $logger)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            /** Single-market row updates (manage prices table) still send one ID. Top form sends many. */
            'market_id' => ['sometimes', 'integer', 'exists:markets,id'],
            'market_ids' => ['sometimes', 'array', 'min:1'],
            'market_ids.*' => ['integer', 'exists:markets,id'],
            'price' => ['required', 'numeric', 'min:1'],
            'effective_date' => ['required', 'date'],
        ]);

        $marketIds = [];
        if ($request->filled('market_ids') && count((array) $request->input('market_ids'))) {
            foreach ((array) $request->input('market_ids') as $mid) {
                $marketIds[] = (int) $mid;
            }
            $marketIds = array_values(array_unique($marketIds));
        }
        if (! count($marketIds) && $request->filled('market_id')) {
            $marketIds = [(int) $request->input('market_id')];
        }

        if (! count($marketIds)) {
            return back()->withErrors(['market_ids' => 'Select at least one market.']);
        }

        foreach ($marketIds as $marketId) {
            $snapshot = $snapshots->upsertManualSnapshot(
                (int) $data['product_id'],
                $marketId,
                (float) $data['price'],
                $data['effective_date']
            );

            $logger->log(Auth::guard('admin')->user(), 'Updated market price', PriceSnapshot::class, $snapshot->id, [
                'product_id' => (int) $data['product_id'],
                'market_id' => $marketId,
                'price' => (float) $data['price'],
                'effective_date' => $data['effective_date'],
            ]);
        }

        return back()->with('status', count($marketIds) > 1
            ? sprintf('Prices saved for %d markets.', count($marketIds))
            : 'Price saved.');
    }

    public function approveExternalSeed(
        int $id,
        Request $request,
        PriceSnapshotService $snapshots,
        AdminActivityLogger $logger
    ) {
        $seed = ExternalPriceSeed::query()->findOrFail($id);
        if ($seed->status === ExternalPriceSeed::STATUS_APPROVED) {
            return back()->with('status', 'This external price is already approved.');
        }

        if (! $seed->product_id || ! $seed->market_id || ! $seed->normalized_price || ! $seed->effective_date) {
            return back()->withErrors(['external' => 'This external price is missing product, market, price, or date.']);
        }

        $snapshot = $snapshots->upsertExternalSnapshot(
            (int) $seed->product_id,
            (int) $seed->market_id,
            (float) $seed->normalized_price,
            $seed->effective_date->toDateString()
        );

        $seed->update([
            'status' => ExternalPriceSeed::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => null,
        ]);

        $logger->log(Auth::guard('admin')->user(), 'Approved external price', ExternalPriceSeed::class, $seed->id, [
            'snapshot_id' => $snapshot->id,
        ]);

        return back()->with('status', 'External price approved and added to the app.');
    }

    public function storeMarket(Request $request, AdminActivityLogger $logger)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:64'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['city'] = $data['city'] ?? 'Abuja';
        $data['state'] = $data['state'] ?? 'FCT';
        $data['is_active'] = $request->boolean('is_active', true);

        $market = Market::query()->create($data);
        $logger->log(Auth::guard('admin')->user(), 'Created market', Market::class, $market->id);

        return back()->with('status', 'Market saved.');
    }

    public function updateMarket(int $id, Request $request, AdminActivityLogger $logger)
    {
        $market = Market::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $market->update([
            'name' => $data['name'],
            'area' => $data['area'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        $logger->log(Auth::guard('admin')->user(), 'Updated market', Market::class, $market->id);

        return back()->with('status', 'Market updated.');
    }

    public function storeProduct(Request $request, AdminActivityLogger $logger)
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['slug'] = Str::slug($data['name']).'-tmp';
        $product = Product::query()->create($data);
        $product->update(['slug' => Str::slug($data['name']).'-'.$product->id]);
        $logger->log(Auth::guard('admin')->user(), 'Created product', Product::class, $product->id);

        return back()->with('status', 'Product saved.');
    }

    public function updateProduct(int $id, Request $request, AdminActivityLogger $logger)
    {
        $product = Product::query()->findOrFail($id);
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:64'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product->update([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.$product->id,
            'unit' => $data['unit'],
            'is_active' => $request->boolean('is_active'),
        ]);
        $logger->log(Auth::guard('admin')->user(), 'Updated product', Product::class, $product->id);

        return back()->with('status', 'Product updated.');
    }

    public function updateUser(int $id, Request $request, AdminActivityLogger $logger)
    {
        $user = User::query()->findOrFail($id);
        $data = $request->validate([
            'role' => ['required', 'in:user,moderator,admin'],
            'banned' => ['nullable', 'boolean'],
        ]);

        $user->update([
            'role' => $data['role'],
            'banned_at' => $request->boolean('banned') ? ($user->banned_at ?? now()) : null,
        ]);
        $logger->log(Auth::guard('admin')->user(), 'Updated user', User::class, $user->id);

        return back()->with('status', 'User updated.');
    }

    public function approveSubmission(int $id, Request $request, GamificationService $gamification, WalletService $wallet, AdminActivityLogger $logger)
    {
        $data = $request->validate([
            'confidence_level' => ['nullable', 'in:high,medium,low'],
        ]);
        $confidence = $data['confidence_level'] ?? 'medium';

        $submission = PriceSubmission::query()->findOrFail($id);
        if ($submission->status !== PriceSubmission::STATUS_APPROVED) {
            $submission->update([
                'status' => PriceSubmission::STATUS_APPROVED,
                'reviewed_at' => now(),
                'reviewed_by' => null,
                'rejection_reason' => null,
            ]);
            $fresh = $submission->fresh();
            $gamification->recomputeSnapshotIfApproved($fresh);
            $this->applyAdminConfidence(
                (int) $fresh->product_id,
                (int) $fresh->market_id,
                $confidence
            );
            $wallet->awardForVerifiedSubmission($fresh);
            $logger->log(Auth::guard('admin')->user(), 'Approved submission', PriceSubmission::class, $submission->id, [
                'confidence_level' => $confidence,
            ]);
        }

        return back()->with('status', 'Submission approved.');
    }

    private function applyAdminConfidence(int $productId, int $marketId, string $level): void
    {
        $snapshot = PriceSnapshot::query()
            ->where('product_id', $productId)
            ->where('market_id', $marketId)
            ->whereDate('snapshot_date', now()->toDateString())
            ->first();

        if (! $snapshot) {
            return;
        }

        if ($level === 'high') {
            $snapshot->update([
                'low_confidence' => false,
                'submission_count' => max((int) $snapshot->submission_count, 3),
            ]);
        } elseif ($level === 'low') {
            $snapshot->update([
                'low_confidence' => true,
            ]);
        } else {
            // medium / "confident"
            $snapshot->update([
                'low_confidence' => false,
                'submission_count' => max(1, min((int) $snapshot->submission_count, 2)),
            ]);
        }
    }

    public function rejectSubmission(int $id, Request $request, AdminActivityLogger $logger)
    {
        $submission = PriceSubmission::query()->findOrFail($id);
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $submission->update([
            'status' => PriceSubmission::STATUS_REJECTED,
            'reviewed_at' => now(),
            'reviewed_by' => null,
            'rejection_reason' => $data['reason'] ?? 'Rejected by admin.',
        ]);
        $logger->log(Auth::guard('admin')->user(), 'Rejected submission', PriceSubmission::class, $submission->id);

        return back()->with('status', 'Submission rejected.');
    }

    public function claims(): View
    {
        return view('admin.claims', [
            'claims' => AirtimeClaim::query()
                ->with(['user', 'payer'])
                ->orderByDesc('claimed_at')
                ->limit(200)
                ->get(),
            'pendingCount' => AirtimeClaim::query()->where('status', AirtimeClaim::STATUS_PENDING)->count(),
        ]);
    }

    public function markClaimPaid(int $id, Request $request, WalletService $wallet, AdminActivityLogger $logger)
    {
        $claim = AirtimeClaim::query()->findOrFail($id);
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $wallet->markPaid($claim, Auth::guard('admin')->user(), $data['admin_note'] ?? 'Airtime sent.');
        $logger->log(Auth::guard('admin')->user(), 'Marked airtime claim paid', AirtimeClaim::class, $claim->id);

        return back()->with('status', 'Claim marked as paid.');
    }

    public function rejectClaim(int $id, Request $request, WalletService $wallet, AdminActivityLogger $logger)
    {
        $claim = AirtimeClaim::query()->findOrFail($id);
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $wallet->reject($claim, Auth::guard('admin')->user(), $data['admin_note'] ?? 'Rejected by admin.');
        $logger->log(Auth::guard('admin')->user(), 'Rejected airtime claim', AirtimeClaim::class, $claim->id);

        return back()->with('status', 'Claim rejected and wallet refunded.');
    }

    public function apiKeys(): View
    {
        return view('admin.api-keys', [
            'keys' => ApiKey::query()
                ->with('developer')
                ->orderByDesc('created_at')
                ->limit(200)
                ->get(),
        ]);
    }

    public function updateApiKey(int $id, Request $request, AdminActivityLogger $logger)
    {
        $key = ApiKey::query()->findOrFail($id);
        $data = $request->validate([
            'daily_limit' => ['required', 'integer', 'min:10', 'max:50000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $key->update([
            'daily_limit' => (int) $data['daily_limit'],
            'monthly_limit' => max((int) $key->monthly_limit, (int) $data['daily_limit'] * 30),
            'is_active' => $request->boolean('is_active', $key->is_active),
        ]);

        $logger->log(Auth::guard('admin')->user(), 'Updated API key limits', ApiKey::class, $key->id, $data);

        return back()->with('status', 'API key updated.');
    }

    public function revokeApiKey(int $id, Request $request, AdminActivityLogger $logger)
    {
        $key = ApiKey::query()->findOrFail($id);
        $key->update(['is_active' => false]);
        $logger->log(Auth::guard('admin')->user(), 'Revoked API key', ApiKey::class, $key->id);

        return back()->with('status', 'API key revoked.');
    }
}
