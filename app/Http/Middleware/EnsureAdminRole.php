<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->isAdminOrModerator()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
                'errors' => new \stdClass,
            ], 403);
        }

        return $next($request);
    }
}
