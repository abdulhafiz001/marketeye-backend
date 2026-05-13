<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSubmissionController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request): JsonResponse
    {
        $rows = $request->user()
            ->priceSubmissions()
            ->with(['product', 'market'])
            ->orderByDesc('submitted_at')
            ->limit(100)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'status' => $s->status,
                'price' => (float) $s->price,
                'notes' => $s->notes,
                'rejection_reason' => $s->rejection_reason,
                'submitted_at' => $s->submitted_at?->toIso8601String(),
                'product' => [
                    'id' => $s->product?->id,
                    'name' => $s->product?->name,
                ],
                'market' => [
                    'id' => $s->market?->id,
                    'name' => $s->market?->name,
                ],
            ]);

        return $this->success(['submissions' => $rows]);
    }
}
