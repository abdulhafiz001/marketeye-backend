<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AirtimeClaim extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_REJECTED = 'rejected';

    public const MIN_CLAIM_AMOUNT = 200;

    protected $fillable = [
        'user_id',
        'amount',
        'phone',
        'status',
        'admin_note',
        'claimed_at',
        'paid_at',
        'paid_by',
    ];

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
