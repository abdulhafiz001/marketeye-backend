<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceSnapshot extends Model
{
    public const SOURCE_SUBMISSION_AGGREGATE = 'submission_aggregate';

    public const SOURCE_EXTERNAL_SEED = 'external_seed';

    public const SOURCE_MANUAL = 'manual';

    protected $fillable = [
        'product_id',
        'market_id',
        'avg_price',
        'min_price',
        'max_price',
        'submission_count',
        'snapshot_date',
        'low_confidence',
        'snapshot_source',
    ];

    protected function casts(): array
    {
        return [
            'avg_price' => 'decimal:2',
            'min_price' => 'decimal:2',
            'max_price' => 'decimal:2',
            'snapshot_date' => 'date',
            'low_confidence' => 'boolean',
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
}
