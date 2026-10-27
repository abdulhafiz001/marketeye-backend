<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\CatalogSuggestion;
use App\Models\Category;
use App\Models\PriceSubmission;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogSuggestionManageController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $suggestions = CatalogSuggestion::query()->with(['user', 'category'])->where('status', 'pending')
            ->orderBy('created_at')->limit(200)->get()->map(fn (CatalogSuggestion $suggestion) => [
                'id' => $suggestion->id,
                'product_name' => $suggestion->product_name,
                'unit' => $suggestion->unit,
                'category_id' => $suggestion->category_id,
                'category_name' => $suggestion->category?->name ?? $suggestion->category_name,
                'reason' => $suggestion->reason,
                'market_prices' => $suggestion->market_prices,
                'submitted_at' => $suggestion->created_at?->toIso8601String(),
                'user' => ['id' => $suggestion->user?->id, 'name' => $suggestion->user?->name],
            ]);

        return $this->success(['suggestions' => $suggestions]);
    }

    public function approve(int $id, Request $request): JsonResponse
    {
        $result = DB::transaction(function () use ($id) {
            $suggestion = CatalogSuggestion::query()->lockForUpdate()->findOrFail($id);
            if ($suggestion->status !== 'pending') {
                return ['error' => 'This suggestion has already been reviewed.'];
            }

            if (Product::query()->whereRaw('LOWER(name) = ?', [Str::lower($suggestion->product_name)])->where('is_active', true)->exists()) {
                return ['error' => 'A product with this name already exists.'];
            }

            $category = $suggestion->category;
            if (! $category && $suggestion->category_name) {
                $category = Category::query()->firstOrCreate(
                    ['slug' => Str::slug($suggestion->category_name)],
                    ['name' => $suggestion->category_name, 'icon' => 'basket']
                );
            }
            if (! $category) {
                return ['error' => 'A valid category is required before this suggestion can be approved.'];
            }

            $product = Product::query()->create([
                'category_id' => $category->id,
                'name' => $suggestion->product_name,
                'slug' => Str::slug($suggestion->product_name).'-'.Str::lower(Str::random(6)),
                'unit' => $suggestion->unit,
                'description' => null,
                'is_active' => true,
            ]);

            foreach ($suggestion->market_prices as $row) {
                PriceSubmission::query()->create([
                    'product_id' => $product->id,
                    'market_id' => $row['market_id'],
                    'user_id' => $suggestion->user_id,
                    'price' => $row['price'],
                    'quantity_value' => 1,
                    'quantity_unit' => $suggestion->unit,
                    'price_per_unit' => $row['price'],
                    'is_geoverified' => true,
                    'latitude' => $row['latitude'],
                    'longitude' => $row['longitude'],
                    'is_outlier' => false,
                    'notes' => 'Price included with approved product suggestion.',
                    'status' => PriceSubmission::STATUS_PENDING,
                    'submitted_at' => $suggestion->created_at,
                    'wallet_rewarded' => false,
                ]);
            }

            $suggestion->update(['status' => 'approved']);

            return ['product_id' => $product->id, 'category_id' => $category->id];
        });

        if (isset($result['error'])) {
            return $this->failure($result['error'], [], 422);
        }

        return $this->success(['product_id' => $result['product_id'], 'category_id' => $result['category_id']], 'Suggestion approved. Its prices are pending normal price review.');
    }

    public function reject(int $id): JsonResponse
    {
        $suggestion = CatalogSuggestion::query()->find($id);
        if (! $suggestion) {
            return $this->failure('Not found.', [], 404);
        }
        if ($suggestion->status !== 'pending') {
            return $this->failure('This suggestion has already been reviewed.', [], 422);
        }

        $suggestion->update(['status' => 'rejected']);

        return $this->success(['id' => $suggestion->id, 'status' => 'rejected']);
    }
}
