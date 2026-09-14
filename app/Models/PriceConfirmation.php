<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceConfirmation extends Model
{
    public const ACTION_CONFIRM = 'CONFIRM';

    public const ACTION_DISPUTE = 'DISPUTE';

    protected $fillable = [
        'user_id',
        'product_id',
        'market_id',
        'action',
        'reported_price',
        'notes',
        'is_geoverified',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'reported_price' => 'decimal:2',
            'is_geoverified' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
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
}
