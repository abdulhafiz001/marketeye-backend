<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\ApiKeyUsage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePublicApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->header('X-API-Key')
            ?: (str_starts_with((string) $request->bearerToken(), 'me_') ? $request->bearerToken() : null);

        if (! $plain) {
            return response()->json([
                'success' => false,
                'message' => 'API key required. Pass X-API-Key or Authorization: Bearer me_…',
            ], 401);
        }

        $key = ApiKey::findByPlainKey($plain);
        if (! $key) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive API key.',
            ], 401);
        }

        $today = now()->toDateString();
        $usage = ApiKeyUsage::query()->firstOrCreate(
            ['api_key_id' => $key->id, 'usage_date' => $today],
            ['request_count' => 0]
        );

        if ((int) $usage->request_count >= (int) $key->daily_limit) {
            return response()->json([
                'success' => false,
                'message' => 'Daily API request limit reached.',
                'limit' => (int) $key->daily_limit,
                'resets_at' => now()->endOfDay()->toIso8601String(),
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => (string) $key->daily_limit,
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => (string) now()->endOfDay()->timestamp,
            ]);
        }

        $usage->increment('request_count');
        $key->forceFill(['last_used_at' => now()])->save();

        $request->attributes->set('api_key', $key);

        $response = $next($request);

        $freshCount = (int) $usage->fresh()->request_count;
        $response->headers->set('X-RateLimit-Limit', (string) $key->daily_limit);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, (int) $key->daily_limit - $freshCount));
        $response->headers->set('X-RateLimit-Reset', (string) now()->endOfDay()->timestamp);

        return $response;
    }
}
