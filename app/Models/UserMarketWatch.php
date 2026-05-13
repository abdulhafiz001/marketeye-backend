<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMarketWatch extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'market_id',
        'last_price',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'last_price' => 'float',
            'last_checked_at' => 'datetime',
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
