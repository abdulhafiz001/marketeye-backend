<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Market;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketManageController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $rows = Market::query()->orderBy('name')->get()->map(fn (Market $m) => [
            'id' => $m->id,
            'name' => $m->name,
            'area' => $m->area,
            'city' => $m->city,
            'state' => $m->state,
            'latitude' => $m->latitude,
            'longitude' => $m->longitude,
            'description' => $m->description,
            'image' => $m->image,
            'is_active' => (bool) $m->is_active,
        ]);

        return $this->success(['markets' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $market = Market::query()->create($data);

        return $this->success(['market' => ['id' => $market->id]], 'Created.', 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $market = Market::query()->find($id);
        if (! $market) {
            return $this->failure('Not found.', [], 404);
        }

        $market->update($this->validated($request, false));

        return $this->success(['market' => ['id' => $market->id]]);
    }

    public function destroy(int $id): JsonResponse
    {
        $market = Market::query()->find($id);
        if (! $market) {
            return $this->failure('Not found.', [], 404);
        }

        $market->update(['is_active' => false]);

        return $this->success(null, 'Market deactivated.');
    }

    private function validated(Request $request, bool $required = true): array
    {
        $rules = [
            'name' => [$required ? 'required' : 'sometimes', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'state' => ['sometimes', 'string', 'max:64'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        return $request->validate($rules);
    }
}
