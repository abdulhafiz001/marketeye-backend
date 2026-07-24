<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\ExternalDataSeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExternalSeedController extends Controller
{
    use ApiResponse;

    /**
     * Create stale-snapshot review items for admin approval.
     */
    public function run(Request $request, ExternalDataSeedService $service): JsonResponse
    {
        return $this->success([
            'stale_review' => $service->runStaleSnapshotReview($request->user()),
        ]);
    }
}
