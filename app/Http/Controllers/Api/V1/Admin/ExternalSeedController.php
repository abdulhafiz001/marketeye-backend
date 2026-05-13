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
     * POST body: { "source": "worldbank" | "wfp" | "all" } (default all)
     */
    public function run(Request $request, ExternalDataSeedService $service): JsonResponse
    {
        $source = $request->input('source', 'all');
        $data = [];

        if ($source === 'all' || $source === 'worldbank') {
            $data['worldbank'] = $service->runWorldBankSeed($request->user());
        }

        if ($source === 'all' || $source === 'wfp') {
            $data['wfp'] = $service->runWfpSeed($request->user());
        }

        return $this->success($data);
    }

    public function worldBank(Request $request, ExternalDataSeedService $service): JsonResponse
    {
        return $this->success(['worldbank' => $service->runWorldBankSeed($request->user())]);
    }

    public function wfp(Request $request, ExternalDataSeedService $service): JsonResponse
    {
        return $this->success(['wfp' => $service->runWfpSeed($request->user())]);
    }
}
