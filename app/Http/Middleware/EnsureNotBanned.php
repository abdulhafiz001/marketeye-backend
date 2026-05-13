<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->isBanned()) {
            return response()->json([
                'success' => false,
                'message' => 'Account suspended.',
                'errors' => new \stdClass,
            ], 403);
        }

        return $next($request);
    }
}
