<?php

namespace App\Repositories;

use App\Models\Market;
use Illuminate\Support\Collection;

class MarketRepository
{
    public function listActive(): Collection
    {
        return Market::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
