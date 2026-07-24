<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Must run before StartSession so admin/developer get separate cookies.
        $middleware->web(prepend: [
            \App\Http\Middleware\ConfigurePortalSession::class,
        ]);

        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('developer') || $request->is('developer/*')) {
                return route('developer.login');
            }

            return route('admin.login');
        });

        $middleware->alias([
            'not_banned' => \App\Http\Middleware\EnsureNotBanned::class,
            'admin_role' => \App\Http\Middleware\EnsureAdminRole::class,
            'web_admin' => \App\Http\Middleware\EnsureWebAdmin::class,
            'developer' => \App\Http\Middleware\EnsureDeveloper::class,
            'public_api_key' => \App\Http\Middleware\EnsurePublicApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], $e->status);
            }

            return null;
        });
    })->create();
