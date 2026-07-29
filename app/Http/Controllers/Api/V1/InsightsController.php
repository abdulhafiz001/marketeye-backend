<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;

class InsightsController extends Controller
{
    use ApiResponse;

    public function __invoke(AnalyticsService $analytics): JsonResponse
    {
        return $this->success($analytics->publicInsights());
    }
}
