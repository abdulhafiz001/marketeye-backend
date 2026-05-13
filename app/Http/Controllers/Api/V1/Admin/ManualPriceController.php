<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\AdminActivityLogger;
use App\Services\PriceSnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManualPriceController extends Controller
{
    use ApiResponse;

    public function store(Request $request, PriceSnapshotService $snapshots, AdminActivityLogger $logger): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'market_id' => ['required', 'integer', 'exists:markets,id'],
            'price' => ['required', 'numeric', 'min:1'],
            'effective_date' => ['required', 'date'],
        ]);

        $snapshot = $snapshots->upsertManualSnapshot(
            (int) $data['product_id'],
            (int) $data['market_id'],
            (float) $data['price'],
            $data['effective_date']
        );

        $logger->log($request->user(), 'manual_price_entry', 'price_snapshot', $snapshot->id, [
            'product_id' => (int) $data['product_id'],
            'market_id' => (int) $data['market_id'],
            'price' => (float) $data['price'],
            'effective_date' => $data['effective_date'],
        ]);

        return $this->success(['snapshot' => ['id' => $snapshot->id]], 'Snapshot created.');
    }
}
