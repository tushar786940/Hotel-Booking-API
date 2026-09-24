<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom rate limiting middleware
 *
 * WHY not use Laravel's built-in throttle?
 * The built-in one works great for web apps (returns HTML).
 * For APIs, we need:
 * 1. JSON error responses (not "429 Too Many Requests" HTML page)
 * 2. Consistent error format matching our API style
 * 3. Logging for monitoring abuse
 * 4. Custom headers for frontend consumption
 *
 * This middleware wraps Laravel's RateLimiter facade
 * and adds our custom behavior on top.
 */
class CustomThrottleRequests
{
    public function __construct(
        protected RateLimiter $limiter
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string  $limiterName  The rate limiter to use (e.g., 'api', 'auth')
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $limiterName = 'api'): Response
    {
        /*
         * ─── STEP 1: Get the limiter instance ───
         *
         * resolveLimiter() looks up the named limiter we defined in AppServiceProvider.
         *
         * It returns an array of Limit objects because one limiter can return multiple limits
         * (e.g., 60/minute AND 1000/hour).
         */
        $limiter = $this->limiter->limiter($limiterName);

        if (!$limiter) {
            // If limiter not found, just pass through
            return $next($request);
        }

        // Execute the limiter callback to get Limit objects
        $limits = $limiter($request);

        // Ensure it's an array (single Limit → array of Limits)
        if (!is_array($limits)) {
            $limits = [$limits];
        }

        /*
         * ─── STEP 2: Check each limit ───
         *
         * For each limit, check if the user has exceeded it.
         */
        foreach ($limits as $limit) {
            // Generate a unique key for this limit
            // e.g., "rate_limit:api:192.168.1.1"
            $key = $limit->key ?? ($limiterName . ':' . ($request->user()?->id ?: $request->ip()));

            // How many requests have been made in this window?
            $attempts = $this->limiter->attempts($key);

            // What's the maximum allowed?
            $maxAttempts = $limit->maxAttempts;

            // How many seconds until the limit resets?
            $decaySeconds = $limit->decaySeconds;

            /*
             * ─── STEP 3: Too many requests? ───
             */
            if ($attempts >= $maxAttempts) {
                // Calculate retry-after time
                $retryAfter = $this->limiter->availableIn($key);

                // LOG the abuse!
                // This helps identify attackers and adjust limits.
                Log::warning('Rate limit exceeded', [
                    'limiter'    => $limiterName,
                    'ip'         => $request->ip(),
                    'user_id'    => $request->user()?->id,
                    'url'        => $request->fullUrl(),
                    'method'     => $request->method(),
                    'attempts'   => $attempts,
                    'limit'      => $maxAttempts,
                    'retry_after' => $retryAfter,
                ]);

                // Return custom JSON response
                return $this->buildResponse($request, $limiterName, $maxAttempts, $retryAfter);
            }

            /*
             * ─── STEP 4: Increment the counter ───
             *
             * hit() increments the request count and sets the TTL.
             * The counter automatically resets after decaySeconds.
             */
            $this->limiter->hit($key, $decaySeconds);
        }

        /*
         * ─── STEP 5: Add rate limit headers to response ───
         *
         * Even successful responses include rate limit info.
         * This lets the frontend show "X requests remaining"
         * and proactively slow down before hitting the limit.
         */
        $response = $next($request);

        return $this->addHeaders($response, $limiterName, $limits, $request);
    }

    /**
     * Build a custom 429 JSON response
     *
     * WHY custom? The default Laravel 429 response is HTML.
     * API clients expect JSON. This ensures consistency.
     */
    protected function buildResponse(
        Request $request,
        string $limiterName,
        int $maxAttempts,
        int $retryAfter
    ): Response {
        // Custom messages per limiter type
        $messages = [
            'auth'    => 'Too many authentication attempts. Please wait before trying again.',
            'search'  => 'Search rate limit exceeded. Please refine your query and try again shortly.',
            'booking' => 'Too many booking attempts. Please wait a moment.',
            'payment' => 'Payment processing limit reached. Please wait before retrying.',
            'invoice' => 'Invoice generation limit reached. Please try again in a minute.',
            'upload'  => 'Upload limit exceeded. Please wait before uploading more files.',
            'webhook' => 'Webhook rate limit exceeded.',
            'api'     => 'API rate limit exceeded. Please slow down your requests.',
        ];

        $message = $messages[$limiterName] ?? $messages['api'];

        return response()->json([
            'message'     => $message,
            'error_code'  => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $retryAfter,
            'limit'       => $maxAttempts,
            'docs'        => config('app.url') . '/docs/rate-limiting',
        ], 429, [
            'Retry-After'           => $retryAfter,
            'X-RateLimit-Limit'     => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset'     => time() + $retryAfter,
        ]);
    }

    /**
     * Add rate limit headers to a successful response
     *
     * Standard headers (RFC 6585):
     * - X-RateLimit-Limit: Maximum requests allowed
     * - X-RateLimit-Remaining: Requests left in current window
     * - X-RateLimit-Reset: Unix timestamp when the limit resets
     *
     * WHY include these?
     * Good API clients read these headers and self-regulate.
     * Example: If Remaining = 2, the client slows down.
     */
    protected function addHeaders(
        Response $response,
        string $limiterName,
        array $limits,
        Request $request
    ): Response {
        $limit = $limits[0] ?? null;

        if (!$limit) {
            return $response;
        }

        $key          = $limit->key ?? ($limiterName . ':' . ($request->user()?->id ?: $request->ip()));
        $maxAttempts  = $limit->maxAttempts;
        $attempts     = $this->limiter->attempts($key);
        $remaining    = max(0, $maxAttempts - $attempts);
        $resetTime    = time() + $this->limiter->availableIn($key);

        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $remaining);
        $response->headers->set('X-RateLimit-Reset', $resetTime);

        return $response;
    }
}
