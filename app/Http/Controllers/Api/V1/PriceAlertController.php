<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\PriceAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceAlertController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $alerts = PriceAlert::query()
            ->with(['product', 'market'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PriceAlert $alert) => $this->serialize($alert));

        return $this->success(['alerts' => $alerts]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'market_id' => ['required', 'integer', 'exists:markets,id'],
            'target_price' => ['required', 'numeric', 'min:1'],
            'condition' => ['required', 'string', 'in:ABOVE,BELOW,above,below'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $condition = strtoupper($data['condition']);

        $alert = PriceAlert::query()->create([
            'user_id' => $request->user()->id,
            'product_id' => (int) $data['product_id'],
            'market_id' => (int) $data['market_id'],
            'target_price' => (float) $data['target_price'],
            'condition' => $condition,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $alert->load(['product', 'market']);

        return $this->success(['alert' => $this->serialize($alert)], 'Alert created.', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $alert = PriceAlert::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (! $alert) {
            return $this->failure('Alert not found.', [], 404);
        }

        $data = $request->validate([
            'target_price' => ['sometimes', 'numeric', 'min:1'],
            'condition' => ['sometimes', 'string', 'in:ABOVE,BELOW,above,below'],
            'is_active' => ['sometimes', 'boolean'],
            'last_triggered_at' => ['sometimes', 'nullable', 'date'],
            'last_known_price' => ['sometimes', 'nullable', 'numeric'],
        ]);

        if (isset($data['condition'])) {
            $data['condition'] = strtoupper($data['condition']);
        }

        $alert->update($data);
        $alert->load(['product', 'market']);

        return $this->success(['alert' => $this->serialize($alert)], 'Alert updated.');
    }

    public function acknowledge(Request $request, int $id): JsonResponse
    {
        $alert = PriceAlert::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (! $alert) {
            return $this->failure('Alert not found.', [], 404);
        }

        $action = $request->input('action', 'acknowledge');

        if ($action === 'deactivate') {
            $alert->update([
                'is_active' => false,
                'last_triggered_at' => now(),
            ]);
        } else {
            $alert->update([
                'last_triggered_at' => now(),
            ]);
        }

        $alert->load(['product', 'market']);

        return $this->success(['alert' => $this->serialize($alert)], 'Alert acknowledged.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $alert = PriceAlert::query()
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (! $alert) {
            return $this->failure('Alert not found.', [], 404);
        }

        $alert->delete();

        return $this->success([], 'Alert deleted.');
    }

    private function serialize(PriceAlert $alert): array
    {
        return [
            'id' => $alert->id,
            'product_id' => (int) $alert->product_id,
            'product_name' => $alert->product?->name,
            'market_id' => (int) $alert->market_id,
            'market_name' => $alert->market?->name,
            'target_price' => (float) $alert->target_price,
            'condition' => strtolower($alert->condition) === 'above' ? 'above' : 'below',
            'is_active' => (bool) $alert->is_active,
            'last_triggered_at' => $alert->last_triggered_at?->toIso8601String(),
            'last_known_price' => $alert->last_known_price !== null ? (float) $alert->last_known_price : null,
            'created_at' => $alert->created_at?->toIso8601String(),
        ];
    }
}
