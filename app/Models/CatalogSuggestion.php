<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogSuggestion extends Model
{
    protected $fillable = [
        'user_id', 'product_name', 'category_id', 'category_name', 'unit', 'reason', 'market_prices', 'status',
    ];

    protected function casts(): array
    {
        return ['market_prices' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
