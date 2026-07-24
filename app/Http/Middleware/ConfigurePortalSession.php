<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Give admin and developer portals separate session cookies so they cannot share auth state.
 */
class ConfigurePortalSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin') || $request->is('admin/*')) {
            config([
                'session.cookie' => 'marketeye_admin_session',
                'session.path' => '/admin',
            ]);
        } elseif ($request->is('developer') || $request->is('developer/*')) {
            config([
                'session.cookie' => 'marketeye_developer_session',
                'session.path' => '/developer',
            ]);
        }

        return $next($request);
    }
}
