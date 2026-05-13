<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\PriceSnapshot;
use App\Models\UserMarketWatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserMarketWatchController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $watches = UserMarketWatch::query()
            ->with(['product', 'market'])
            ->where('user_id', $request->user()->id)
            ->latest('updated_at')
            ->get()
            ->map(fn (UserMarketWatch $watch) => $this->present($watch))
            ->values();

        return $this->success(['watches' => $watches]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'market_id' => ['required', 'integer', 'exists:markets,id'],
            'last_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $watch = UserMarketWatch::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'product_id' => (int) $data['product_id'],
                'market_id' => (int) $data['market_id'],
            ],
            [
                'last_price' => isset($data['last_price']) ? (float) $data['last_price'] : null,
                'last_checked_at' => now(),
            ]
        );

        return $this->success(['watch' => $this->present($watch->fresh(['product', 'market']))], 'Saved.', 201);
    }

    public function destroy(int $productId, int $marketId, Request $request): JsonResponse
    {
        UserMarketWatch::query()
            ->where('user_id', $request->user()->id)
            ->where('product_id', $productId)
            ->where('market_id', $marketId)
            ->delete();

        return $this->success(['removed' => true], 'Removed.');
    }

    private function present(UserMarketWatch $watch): array
    {
        $latest = PriceSnapshot::query()
            ->where('product_id', $watch->product_id)
            ->where('market_id', $watch->market_id)
            ->orderByDesc('snapshot_date')
            ->first();

        $lastPrice = $latest?->avg_price ?? $watch->last_price;
        $lastCheckedAt = $latest?->snapshot_date?->toDateString()
            ?? $watch->last_checked_at?->toISOString();

        return [
            'id' => "{$watch->market_id}:{$watch->product_id}",
            'product_id' => $watch->product_id,
            'product_name' => $watch->product?->name,
            'unit' => $watch->product?->unit,
            'market_id' => $watch->market_id,
            'market_name' => $watch->market?->name,
            'last_price' => $lastPrice !== null ? (float) $lastPrice : null,
            'last_checked_at' => $lastCheckedAt,
        ];
    }
}
