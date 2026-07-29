<?php

namespace App\Jobs;

use App\Models\PriceAlert;
use App\Models\PriceSnapshot;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPriceAlertsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $productId,
        public int $marketId,
        public float $currentPrice,
        public string $snapshotDate,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        $alerts = PriceAlert::query()
            ->with(['user', 'product', 'market'])
            ->where('product_id', $this->productId)
            ->where('market_id', $this->marketId)
            ->where('is_active', true)
            ->get();

        if ($alerts->isEmpty()) {
            return;
        }

        $sent = 0;

        foreach ($alerts as $alert) {
            if (! $alert->isTriggeredBy($this->currentPrice)) {
                $alert->update(['last_known_price' => $this->currentPrice]);

                continue;
            }

            if (
                $alert->last_triggered_at
                && $alert->last_triggered_at->gt(now()->subHours(6))
            ) {
                continue;
            }

            $productName = $alert->product?->name ?? 'Product';
            $marketName = $alert->market?->name ?? 'market';
            $direction = $alert->condition === PriceAlert::CONDITION_BELOW ? 'dropped to' : 'rose to';
            $priceLabel = '₦'.number_format($this->currentPrice, 0);
            $title = "Price alert: {$productName}";
            $body = "{$productName} {$direction} {$priceLabel} at {$marketName} ({$this->snapshotDate}).";

            $alert->update([
                'last_triggered_at' => now(),
                'last_known_price' => $this->currentPrice,
            ]);

            if (! $alert->user) {
                continue;
            }

            $ok = $push->notifyUser($alert->user, $title, $body, [
                'type' => 'price_alert',
                'alert_id' => $alert->id,
                'product_id' => $this->productId,
                'market_id' => $this->marketId,
                'price' => $this->currentPrice,
                'snapshot_date' => $this->snapshotDate,
            ]);

            if ($ok) {
                $sent++;
            }
        }

        Log::info('ProcessPriceAlertsJob finished', [
            'product_id' => $this->productId,
            'market_id' => $this->marketId,
            'alerts_checked' => $alerts->count(),
            'pushes_sent' => $sent,
        ]);
    }

    public static function dispatchForSnapshot(PriceSnapshot $snapshot): void
    {
        self::dispatch(
            (int) $snapshot->product_id,
            (int) $snapshot->market_id,
            (float) $snapshot->avg_price,
            $snapshot->snapshot_date?->toDateString() ?? now()->toDateString(),
        );
    }
}
