<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    /**
     * @param  list<array{to: string, title: string, body: string, data?: array<string, mixed>}>  $messages
     */
    public function send(array $messages): void
    {
        if ($messages === []) {
            return;
        }

        foreach (array_chunk($messages, 100) as $chunk) {
            try {
                $response = Http::acceptJson()
                    ->asJson()
                    ->timeout(15)
                    ->post(self::ENDPOINT, $chunk);

                if (! $response->successful()) {
                    Log::warning('Expo push failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Expo push exception: '.$e->getMessage());
            }
        }
    }

    public function notify(string $token, string $title, string $body, array $data = []): void
    {
        if (! $this->isValidToken($token)) {
            return;
        }

        $this->send([[
            'to' => $token,
            'sound' => 'default',
            'title' => $title,
            'body' => $body,
            'channelId' => 'price-alerts',
            'priority' => 'high',
            'data' => $data,
        ]]);
    }

    public function isValidToken(string $token): bool
    {
        return str_starts_with($token, 'ExponentPushToken[')
            || str_starts_with($token, 'ExpoPushToken[');
    }
}
