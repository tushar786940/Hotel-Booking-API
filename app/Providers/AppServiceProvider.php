<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     *
     * WHY define limiters here?
     * Centralized configuration = easy to adjust limits
     * without touching route files. Change one number,
     * and the limit updates everywhere it's used.
     */
    protected function configureRateLimiting(): void
    {
        /*
         * ─── 1. GLOBAL API LIMIT ───
         *
         * Applied to ALL API requests as a safety net.
         *
         * Limit: 60 requests per minute per IP address
         *
         * WHY per IP?
         * Unauthenticated users don't have a user ID.
         * IP is the only identifier we have for them.
         *
         * WHY 60/minute?
         * Normal browsing generates ~10-20 requests/minute.
         * 60 gives plenty of headroom while blocking abuse.
         */
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip());
            //       ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
            //       If logged in, limit by user ID.
            //       If not logged in, limit by IP address.
            //
            //       WHY user ID for logged-in users?
            //       Multiple users behind the same IP (office, school)
            //       shouldn't share a limit. Each user gets their own.
        });

        /*
         * ─── 2. AUTHENTICATION LIMIT (STRICT!) ───
         *
         * Applied to login and register endpoints.
         *
         * Limit: 5 attempts per minute per IP
         *
         * WHY so strict?
         * Brute force attacks try thousands of passwords per second.
         * 5 attempts/minute makes brute force impractical:
         *   - 10-character password = 10^10 combinations
         *   - At 5/min = 2 billion years to crack 😄
         *
         * The 'response' callback customizes the error message.
         */
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many login attempts. Please try again in '
                            . ceil($headers['Retry-After'] / 60) . ' minute(s).',
                        'errors' => [
                            'email' => ['Too many attempts. Account temporarily locked.'],
                        ],
                        'retry_after' => (int) $headers['Retry-After'],
                    ], 429, $headers);
                });
        });

        /*
         * ─── 3. SEARCH LIMIT ───
         *
         * Search queries are EXPENSIVE (database queries,
         * availability checks, price calculations).
         *
         * Limit: 30 requests per minute per user/IP
         *
         * WHY lower than global?
         * Each search hits multiple tables and runs complex
         * date overlap checks. 30/min prevents server overload.
         */
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->user()?->id ?: $request->ip());
        });

        /*
         * ─── 4. BOOKING LIMIT ───
         *
         * Booking creation involves transactions, email sending,
         * and payment processing.
         *
         * Limit: 10 requests per minute per user
         *
         * WHY per user (not IP)?
         * Only authenticated users can book. We want to prevent
         * a single user from creating hundreds of fake bookings.
         */
        RateLimiter::for('booking', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user()?->id ?: $request->ip());
        });

        /*
         * ─── 5. PAYMENT LIMIT (VERY STRICT!) ───
         *
         * Payment endpoints talk to Stripe (external API).
         * Too many requests = Stripe rate limits US.
         *
         * Limit: 5 requests per minute per user
         */
        RateLimiter::for('payment', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many payment attempts. Please wait before trying again.',
                        'retry_after' => (int) $headers['Retry-After'],
                    ], 429, $headers);
                });
        });

        /*
         * ─── 6. ROLE-BASED LIMITS ───
         *
         * Different roles get different limits.
         * Admins and hotel owners need higher limits
         * because they manage multiple hotels/bookings.
         *
         * Admin:       300 requests/minute
         * Hotel Owner: 120 requests/minute
         * Guest:       60 requests/minute
         */
        RateLimiter::for('role-based', function (Request $request) {
            $user = $request->user();

            if (!$user) {
                return Limit::perMinute(60)->by($request->ip());
            }

            // Admin gets highest limit
            if ($user->hasRole('admin')) {
                return Limit::perMinute(300)->by($user->id);
            }

            // Hotel owner gets moderate limit
            if ($user->hasRole('hotel-owner')) {
                return Limit::perMinute(120)->by($user->id);
            }

            // Regular guest gets standard limit
            return Limit::perMinute(60)->by($user->id);
        });

        /*
         * ─── 7. INVOICE / PDF LIMIT ───
         *
         * PDF generation is CPU-intensive.
         * DomPDF renders HTML → PDF which takes 1-3 seconds.
         *
         * Limit: 5 requests per minute per user
         */
        RateLimiter::for('invoice', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->user()?->id ?: $request->ip());
        });

        /*
         * ─── 8. IMAGE UPLOAD LIMIT ───
         *
         * Image uploads consume bandwidth and storage.
         * Processing (resize + thumbnail) uses CPU.
         *
         * Limit: 10 uploads per minute per user
         */
        RateLimiter::for('upload', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user()?->id ?: $request->ip());
        });

        /*
         * ─── 9. WEBHOOK LIMIT ───
         *
         * Stripe webhooks should have a generous limit
         * since Stripe may retry failed webhooks rapidly.
         *
         * Limit: 100 requests per minute per Stripe IP
         */
        RateLimiter::for('webhook', function (Request $request) {
            return Limit::perMinute(100)
                ->by($request->ip());
        });
    }
}