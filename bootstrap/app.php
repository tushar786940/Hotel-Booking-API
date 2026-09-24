<?php

use App\Http\Middleware\CustomThrottleRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register our custom throttle middleware with an alias
        $middleware->alias([
            'throttle.custom' => CustomThrottleRequests::class,
        ]);

        // Apply global rate limiting to all API routes
        $middleware->api(prepend: [
            'throttle.custom:role-based',
        ]);

        // Exclude webhook from CSRF
        $middleware->validateCsrfTokens(except: [
            'api/v1/webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle ThrottleRequestsException for API routes
        $exceptions->render(function (ThrottleRequestsException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message'     => 'Too many requests. Please slow down.',
                    'error_code'  => 'RATE_LIMIT_EXCEEDED',
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? 60,
                ], 429, $e->getHeaders());
            }
        });
    })
    ->create();
