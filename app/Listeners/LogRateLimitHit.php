<?php

namespace App\Listeners;

use Illuminate\Cache\Events\RateLimitExceeded;
use Illuminate\Support\Facades\Log;

/**
 * Logs every rate limit violation
 *
 * WHY monitor?
 * - Detect brute force attacks in real-time
 * - Identify misconfigured clients
 * - Adjust limits based on actual usage
 * - Alert the team about suspicious activity
 */
class LogRateLimitHit
{
    public function handle(RateLimitExceeded $event): void
    {
        Log::channel('daily')->warning('Rate limit exceeded', [
            'key'      => $event->key,
            'ip'       => request()->ip(),
            'user'     => request()->user()?->id,
            'url'      => request()->fullUrl(),
            'method'   => request()->method(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}