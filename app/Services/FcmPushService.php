<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmPushService
{
    public function isConfigured(): bool
    {
        $path = (string) config('firebase.credentials');

        return is_file($path) && is_readable($path);
    }

    public function notify(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        $deviceToken = trim($deviceToken);
        if ($deviceToken === '' || ! $this->isConfigured()) {
            return false;
        }

        $projectId = (string) (config('firebase.project_id') ?: $this->credentials()['project_id'] ?? '');
        if ($projectId === '') {
            Log::warning('FCM: missing project_id');

            return false;
        }

        $accessToken = $this->accessToken();
        if ($accessToken === null) {
            return false;
        }

        $stringData = [];
        foreach ($data as $key => $value) {
            $stringData[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        $payload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $stringData,
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'channel_id' => (string) config('firebase.android_channel_id', 'price-alerts'),
                        'sound' => 'default',
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->asJson()
                ->timeout(20)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

            if (! $response->successful()) {
                Log::warning('FCM send failed', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('FCM send exception: '.$e->getMessage());

            return false;
        }
    }

    private function accessToken(): ?string
    {
        $cached = Cache::get('firebase_fcm_access_token');
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $creds = $this->credentials();
        $clientEmail = (string) ($creds['client_email'] ?? '');
        $privateKey = (string) ($creds['private_key'] ?? '');
        $tokenUri = (string) ($creds['token_uri'] ?? 'https://oauth2.googleapis.com/token');

        if ($clientEmail === '' || $privateKey === '') {
            Log::warning('FCM: service account missing client_email/private_key');

            return null;
        }

        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $tokenUri,
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $unsigned = $header.'.'.$claims;
        $signature = '';
        $ok = openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (! $ok) {
            Log::warning('FCM: openssl_sign failed for service account JWT');

            return null;
        }

        $jwt = $unsigned.'.'.$this->base64UrlEncode($signature);

        $response = Http::asForm()
            ->timeout(15)
            ->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

        if (! $response->successful()) {
            Log::warning('FCM: OAuth token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $token = $response->json('access_token');
        if (! is_string($token) || $token === '') {
            return null;
        }

        Cache::put('firebase_fcm_access_token', $token, 3000);

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    private function credentials(): array
    {
        $path = (string) config('firebase.credentials');
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
