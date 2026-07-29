<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceAlert extends Model
{
    public const CONDITION_ABOVE = 'ABOVE';

    public const CONDITION_BELOW = 'BELOW';

    protected $fillable = [
        'user_id',
        'product_id',
        'market_id',
        'target_price',
        'condition',
        'is_active',
        'last_triggered_at',
        'last_known_price',
    ];

    protected function casts(): array
    {
        return [
            'target_price' => 'float',
            'last_known_price' => 'float',
            'is_active' => 'boolean',
            'last_triggered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function isTriggeredBy(float $price): bool
    {
        if ($this->condition === self::CONDITION_BELOW) {
            return $price <= (float) $this->target_price;
        }

        if ($this->condition === self::CONDITION_ABOVE) {
            return $price >= (float) $this->target_price;
        }

        return false;
    }
}
