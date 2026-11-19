<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\CatalogSuggestion;
use App\Models\Category;
use App\Models\Market;
use App\Models\Product;
use App\Services\MarketProximityService;
use App\Services\PriceSubmissionService;
use App\Support\CategoryIcon;
use App\Support\UnitNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogSuggestionController extends Controller
{
    use ApiResponse;

    public function store(
        Request $request,
        MarketProximityService $proximity,
        PriceSubmissionService $submissions
    ): JsonResponse {
        $data = $request->validate([
            'product_name' => ['required', 'string', 'min:3', 'max:120', 'regex:/[\pL]{3}/u'],
            'unit' => ['required', 'string', 'min:2', 'max:64', 'regex:/[\pL]{2}/u'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_name' => ['nullable', 'required_without:category_id', 'prohibited_with:category_id', 'string', 'min:3', 'max:80', 'regex:/[\pL]{3}/u'],
            'reason' => ['nullable', 'string', 'max:500'],
            'market_prices' => ['required', 'array', 'min:1', 'max:10'],
            'market_prices.*.market_id' => ['required', 'integer', 'distinct', 'exists:markets,id'],
            'market_prices.*.price' => ['required', 'numeric', 'min:1', 'max:100000000'],
            'market_prices.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'market_prices.*.longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $productName = $this->normalizeName($data['product_name']);
        $cleanUnit = UnitNormalizer::displayUnit($data['unit']);
        $categoryName = isset($data['category_name']) ? $this->normalizeName($data['category_name']) : null;

        if (preg_match('/(.)\1{3,}/u', $productName) || ! preg_match('/[\pL]{3}/u', $productName)) {
            return $this->failure(
                'Enter a real product name with at least three letters.',
                ['product_name' => ['This name does not look like a valid product.']],
                422
            );
        }

        // Validate market proximity first before writing to the database
        foreach ($data['market_prices'] as $index => $row) {
            $market = Market::query()->findOrFail($row['market_id']);
            if (! $market->is_active) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "market_prices.{$index}.market_id" => ["{$market->name} is not currently accepting price reports."],
                ]);
            }
            try {
                $proximity->assertWithinMarket($market, $row['latitude'], $row['longitude']);
            } catch (\Illuminate\Validation\ValidationException $exception) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "market_prices.{$index}.market_id" => collect($exception->errors())->flatten()->all(),
                ]);
            }
        }

        $user = $request->user();

        $published = DB::transaction(function () use ($data, $user, $productName, $cleanUnit, $categoryName, $submissions) {
            // 1. Resolve or create Category immediately
            $category = null;
            if (! empty($data['category_id'])) {
                $category = Category::query()->find((int) $data['category_id']);
            }
            if (! $category && $categoryName) {
                $existingCat = Category::query()->whereRaw('LOWER(name) = ?', [Str::lower($categoryName)])->first();
                if ($existingCat) {
                    $category = $existingCat;
                } else {
                    $slug = Str::slug($categoryName) ?: 'category-'.Str::lower(Str::random(5));
                    $category = Category::query()->create([
                        'name' => $categoryName,
                        'slug' => $slug,
                        'icon' => CategoryIcon::resolve(null, $categoryName, $slug),
                    ]);
                }
            }
            if (! $category) {
                $category = Category::query()->first() ?? Category::query()->create([
                    'name' => 'General Staples',
                    'slug' => 'general-staples',
                    'icon' => 'basket-outline',
                ]);
            }

            // 2. Resolve or create Product (distinguishing different measurement units!)
            $baseName = UnitNormalizer::baseProductName($productName);
            $existingProducts = Product::query()
                ->where('is_active', true)
                ->where(function ($q) use ($productName, $baseName) {
                    $q->whereRaw('LOWER(name) = ?', [Str::lower($productName)])
                        ->orWhereRaw('LOWER(name) = ?', [Str::lower($baseName)])
                        ->orWhereRaw('LOWER(name) LIKE ?', [Str::lower($baseName).' (%']);
                })
                ->get();

            $product = null;
            $hasSameBaseDifferentUnit = false;
            foreach ($existingProducts as $cand) {
                if (Str::lower(UnitNormalizer::baseProductName($cand->name)) === Str::lower($baseName)) {
                    if (UnitNormalizer::areCompatible($cand->unit, $cleanUnit)) {
                        $product = $cand;
                        break;
                    }
                    $hasSameBaseDifferentUnit = true;
                }
            }

            if (! $product) {
                $finalName = $hasSameBaseDifferentUnit
                    ? "{$baseName} ({$cleanUnit})"
                    : $productName;

                $product = Product::query()->create([
                    'category_id' => $category->id,
                    'name' => $finalName,
                    'slug' => Str::slug($finalName).'-'.Str::lower(Str::random(6)),
                    'unit' => $cleanUnit,
                    'description' => isset($data['reason']) ? trim($data['reason']) : null,
                    'is_active' => true,
                ]);
            }

            // 3. Record audit row in catalog_suggestions as approved
            $suggestion = CatalogSuggestion::query()->create([
                'user_id' => $user->id,
                'product_name' => $product->name,
                'category_id' => $category->id,
                'category_name' => $category->name,
                'unit' => $product->unit,
                'market_prices' => $data['market_prices'],
                'reason' => isset($data['reason']) ? trim($data['reason']) : null,
                'status' => 'approved',
            ]);

            // 4. Submit each market price through PriceSubmissionService so snapshots, Manage Submissions,
            // points (+10), and airtime wallet rewards (+₦1) happen immediately!
            $results = [];
            $accepted = 0;
            $failed = 0;
            $totalPoints = 0;
            $totalWallet = 0;

            foreach ($data['market_prices'] as $index => $row) {
                $created = $submissions->submit($user, [
                    'product_id' => $product->id,
                    'market_id' => (int) $row['market_id'],
                    'price' => (float) $row['price'],
                    'quantity_value' => 1,
                    'quantity_unit' => $product->unit,
                    'notes' => isset($data['reason']) ? trim($data['reason']) : null,
                    'latitude' => (float) $row['latitude'],
                    'longitude' => (float) $row['longitude'],
                ]);

                $sub = $created['submission'];
                $accepted++;
                $totalPoints += (int) $created['points_awarded'];
                $totalWallet += (int) $created['wallet_awarded'];

                $results[] = [
                    'index' => $index,
                    'ok' => true,
                    'submission' => [
                        'id' => $sub->id,
                        'status' => $sub->status,
                        'auto_approved' => $created['auto_approved'],
                        'points_awarded' => $created['points_awarded'],
                        'wallet_awarded' => $created['wallet_awarded'],
                    ],
                ];
            }

            return [
                'suggestion' => $suggestion,
                'product' => $product,
                'accepted' => $accepted,
                'failed' => $failed,
                'points_awarded' => $totalPoints,
                'wallet_awarded' => $totalWallet,
                'results' => $results,
            ];
        });

        $user->refresh();

        return $this->success([
            'suggestion' => [
                'id' => $published['suggestion']->id,
                'status' => $published['suggestion']->status,
            ],
            'product' => [
                'id' => $published['product']->id,
                'name' => $published['product']->name,
                'unit' => $published['product']->unit,
            ],
            'accepted' => $published['accepted'],
            'failed' => $published['failed'],
            'points_awarded' => $published['points_awarded'],
            'wallet_awarded' => $published['wallet_awarded'],
            'results' => $published['results'],
            'user' => [
                'points' => (int) $user->points,
                'wallet_balance' => (int) $user->wallet_balance,
            ],
        ], 'New product added and market price published.', 201);
    }

    private function normalizeName(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));

        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }
}
