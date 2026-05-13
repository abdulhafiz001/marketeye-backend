<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalPriceSeed extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'product_id',
        'market_id',
        'source',
        'raw_price',
        'normalized_price',
        'effective_date',
        'status',
        'error_message',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'raw_price' => 'decimal:4',
            'normalized_price' => 'decimal:2',
            'effective_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
