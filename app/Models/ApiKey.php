<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'user_id',
        'developer_id',
        'name',
        'key_prefix',
        'key_hash',
        'daily_limit',
        'monthly_limit',
        'is_active',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(ApiKeyUsage::class);
    }

    /**
     * @return array{model: self, plain: string}
     */
    public static function issueForDeveloper(Developer $developer, string $name, int $dailyLimit): array
    {
        $plain = 'me_'.Str::random(40);
        $model = self::query()->create([
            'developer_id' => $developer->id,
            'user_id' => null,
            'name' => $name,
            'key_prefix' => substr($plain, 0, 12),
            'key_hash' => hash('sha256', $plain),
            'daily_limit' => $dailyLimit,
            'monthly_limit' => max(10000, $dailyLimit * 30),
            'is_active' => true,
        ]);

        return ['model' => $model, 'plain' => $plain];
    }

    public static function findByPlainKey(string $plain): ?self
    {
        return self::query()
            ->where('key_hash', hash('sha256', $plain))
            ->where('is_active', true)
            ->first();
    }
}
