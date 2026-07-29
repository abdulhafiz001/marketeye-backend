<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\PriceSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BatchPriceSubmitController extends Controller
{
    use ApiResponse;

    public function store(Request $request, PriceSubmissionService $submissions): JsonResponse
    {
        $data = $request->validate([
            'submissions' => ['required', 'array', 'min:1', 'max:25'],
            'submissions.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'submissions.*.market_id' => ['required', 'integer', 'exists:markets,id'],
            'submissions.*.price' => ['required', 'numeric', 'min:1'],
            'submissions.*.quantity_value' => ['nullable', 'numeric', 'min:0.001', 'max:999999'],
            'submissions.*.quantity_unit' => ['nullable', 'string', 'max:64'],
            'submissions.*.notes' => ['nullable', 'string', 'max:2000'],
            'submissions.*.client_id' => ['nullable', 'string', 'max:64'],
        ]);

        $user = $request->user();
        $results = [];
        $accepted = 0;
        $failed = 0;

        foreach ($data['submissions'] as $index => $row) {
            $validator = Validator::make($row, [
                'product_id' => ['required', 'integer', 'exists:products,id'],
                'market_id' => ['required', 'integer', 'exists:markets,id'],
                'price' => ['required', 'numeric', 'min:1'],
            ]);

            if ($validator->fails()) {
                $failed++;
                $results[] = [
                    'index' => $index,
                    'client_id' => $row['client_id'] ?? null,
                    'ok' => false,
                    'message' => $validator->errors()->first(),
                ];

                continue;
            }

            try {
                $created = $submissions->submit($user, $row);
                $submission = $created['submission'];
                $accepted++;
                $results[] = [
                    'index' => $index,
                    'client_id' => $row['client_id'] ?? null,
                    'ok' => true,
                    'submission' => [
                        'id' => $submission->id,
                        'status' => $submission->status,
                        'auto_approved' => $created['auto_approved'],
                        'points_awarded' => $created['points_awarded'],
                    ],
                ];
            } catch (\Throwable $e) {
                $failed++;
                $results[] = [
                    'index' => $index,
                    'client_id' => $row['client_id'] ?? null,
                    'ok' => false,
                    'message' => 'Could not save this submission.',
                ];
            }
        }

        $user->refresh();

        return $this->success([
            'accepted' => $accepted,
            'failed' => $failed,
            'results' => $results,
            'user' => [
                'points' => (int) $user->points,
                'wallet_balance' => (int) $user->wallet_balance,
            ],
        ], $accepted > 0 ? 'Batch processed.' : 'No submissions accepted.');
    }
}
