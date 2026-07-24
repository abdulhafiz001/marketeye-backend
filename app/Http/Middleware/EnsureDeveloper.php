<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeveloper
{
    public function handle(Request $request, Closure $next): Response
    {
        $developer = Auth::guard('developer')->user();
        if (! $developer || ! $developer->is_active) {
            return redirect()->route('developer.login');
        }

        return $next($request);
    }
}
