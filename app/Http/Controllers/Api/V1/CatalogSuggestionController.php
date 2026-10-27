<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\CatalogSuggestion;
use App\Models\Category;
use App\Models\Market;
use App\Models\Product;
use App\Services\MarketProximityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CatalogSuggestionController extends Controller
{
    use ApiResponse;

    public function store(Request $request, MarketProximityService $proximity): JsonResponse
    {
        $data = $request->validate([
            'product_name' => ['required', 'string', 'min:3', 'max:120', 'regex:/[\\pL]{3}/u'],
            'unit' => ['required', 'string', 'min:2', 'max:64', 'regex:/[\\pL]{2}/u'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_name' => ['nullable', 'required_without:category_id', 'prohibited_with:category_id', 'string', 'min:3', 'max:80', 'regex:/[\\pL]{3}/u'],
            'reason' => ['nullable', 'string', 'max:500'],
            'market_prices' => ['required', 'array', 'min:1', 'max:10'],
            'market_prices.*.market_id' => ['required', 'integer', 'distinct', 'exists:markets,id'],
            'market_prices.*.price' => ['required', 'numeric', 'min:1', 'max:100000000'],
            'market_prices.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'market_prices.*.longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $productName = $this->normalizeName($data['product_name']);
        $categoryName = isset($data['category_name']) ? $this->normalizeName($data['category_name']) : null;

        if (preg_match('/(.)\\1{3,}/u', $productName) || ! preg_match('/[\\pL]{3}/u', $productName)) {
            return $this->failure('Enter a real product name with at least three letters.', ['product_name' => ['This name does not look like a valid product.']], 422);
        }

        if (Product::query()->whereRaw('LOWER(name) = ?', [Str::lower($productName)])->where('is_active', true)->exists()) {
            return $this->failure('This product is already in the catalog. Search for it and submit its price.', ['product_name' => ['Product already exists.']], 422);
        }

        if (CatalogSuggestion::query()->where('status', 'pending')->whereRaw('LOWER(product_name) = ?', [Str::lower($productName)])->exists()) {
            return $this->failure('This product has already been suggested and is awaiting review.', ['product_name' => ['Duplicate pending suggestion.']], 422);
        }

        if ($categoryName && Category::query()->whereRaw('LOWER(name) = ?', [Str::lower($categoryName)])->exists()) {
            return $this->failure('This category already exists. Select it from the category list.', ['category_name' => ['Category already exists.']], 422);
        }

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

        $suggestion = CatalogSuggestion::query()->create([
            'user_id' => $request->user()->id,
            'product_name' => $productName,
            'category_id' => $data['category_id'] ?? null,
            'category_name' => $categoryName,
            'unit' => trim(preg_replace('/\\s+/', ' ', $data['unit'])),
            'market_prices' => $data['market_prices'],
            'reason' => isset($data['reason']) ? trim($data['reason']) : null,
            'status' => 'pending',
        ]);

        return $this->success([
            'suggestion' => ['id' => $suggestion->id, 'status' => $suggestion->status],
        ], 'Product suggestion sent for review. You can submit its prices after approval.', 201);
    }

    private function normalizeName(string $name): string
    {
        $name = trim(preg_replace('/\\s+/', ' ', $name));

        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }
}
