<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\PriceSnapshot;
use App\Repositories\MarketRepository;
use Illuminate\Http\JsonResponse;

class MarketController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MarketRepository $markets
    ) {}

    public function index(): JsonResponse
    {
        $markets = $this->markets->listActive()->map(fn (Market $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'area' => $m->area,
                'lat' => $m->latitude,
                'lng' => $m->longitude,
            ]);

        $lastUpdate = PriceSnapshot::query()->max('snapshot_date');

        return $this->success([
            'markets' => $markets,
            'meta' => [
                'last_price_update' => $lastUpdate,
            ],
        ]);
    }
}
