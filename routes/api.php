<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HotelController;
use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\RoomTypeController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Api\V1\Admin\AdminHotelController;
use App\Http\Controllers\Api\V1\Admin\AdminBookingController;
use App\Http\Controllers\Api\V1\ImageUploadController;
use App\Http\Controllers\Api\V1\InvoiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| prefix('v1') means all routes start with /api/v1/
| This allows us to create v2 later without breaking v1
*/

Route::prefix('v1')->group(function () {

    // ═══════════════════════════════════════════
    // PUBLIC ROUTES (no authentication needed)
    // ═══════════════════════════════════════════

    // Auth — STRICT rate limit (prevent brute force)
    Route::middleware('throttle.custom:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Hotels — Standard rate limit (inherited from global)
    Route::get('/hotels', [HotelController::class, 'index']);
    Route::get('/hotels/{hotel:slug}', [HotelController::class, 'show']);
    Route::get('/hotels/{hotel}/reviews', [ReviewController::class, 'index']);

    // Search — Custom rate limit (expensive queries)
    Route::middleware('throttle.custom:search')->group(function () {
        Route::get('/search', [AvailabilityController::class, 'search']);
        Route::get('/hotels/{hotel}/availability', [AvailabilityController::class, 'checkHotel']);
    });

    // Webhooks — Generous limit (Stripe retries)
    Route::middleware('throttle.custom:webhook')->group(function () {
        Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripe']);
    });


    // ═══════════════════════════════════════════
    // AUTHENTICATED ROUTES
    // ═══════════════════════════════════════════

    Route::middleware('auth:sanctum')->group(function () {

        // Auth management
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);

        // Bookings — Custom rate limit
        Route::middleware('throttle.custom:booking')->group(function () {
            Route::post('/bookings', [BookingController::class, 'store']);
            Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
        });

        // Read-only booking routes (use global limit)
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::get('/bookings/{booking}', [BookingController::class, 'show']);

        // Payments — Very strict limit
        Route::middleware('throttle.custom:payment')->group(function () {
            Route::post('/bookings/{booking}/pay', [PaymentController::class, 'processPayment']);
        });
        Route::get('/bookings/{booking}/payment-status', [PaymentController::class, 'status']);

        // Reviews
        Route::post('/bookings/{booking}/review', [ReviewController::class, 'store']);

        // Invoices — CPU-intensive PDF generation
        Route::middleware('throttle.custom:invoice')->group(function () {
            Route::get('/bookings/{booking}/invoice', [InvoiceController::class, 'view']);
            Route::get('/bookings/{booking}/invoice/download', [InvoiceController::class, 'download']);
            Route::post('/bookings/{booking}/invoice/email', [InvoiceController::class, 'email']);
        });

        // ═══════════════════════════════════════
        // HOTEL OWNER ROUTES
        // ═══════════════════════════════════════

        Route::middleware('role:hotel-owner')
            ->prefix('manage')
            ->group(function () {

                Route::apiResource('hotels', AdminHotelController::class);

                // Image uploads — Bandwidth-intensive
                Route::middleware('throttle.custom:upload')->group(function () {
                    Route::post('/hotels/{hotel}/images', [ImageUploadController::class, 'uploadHotelImages']);
                    Route::put('/hotels/{hotel}/images', [ImageUploadController::class, 'replaceHotelImages']);
                    Route::post('/room-types/{roomType}/images', [ImageUploadController::class, 'uploadRoomTypeImages']);
                });

                Route::delete('/hotels/{hotel}/images', [ImageUploadController::class, 'deleteHotelImage']);
                Route::put('/hotels/{hotel}/images/reorder', [ImageUploadController::class, 'reorderHotelImages']);

                Route::apiResource('hotels.room-types', RoomTypeController::class)->shallow();
                Route::get('/hotels/{hotel}/bookings', [AdminBookingController::class, 'index']);
                Route::put('/bookings/{booking}/status', [AdminBookingController::class, 'updateStatus']);
                Route::post('/bookings/{booking}/invoice/regenerate', [InvoiceController::class, 'regenerate']);
            });
    });

    Route::get('/rate-limits', function () {
        return response()->json([
            'message' => 'API Rate Limits',
            'data' => [
                'global' => [
                    'limit'  => 60,
                    'window' => '1 minute',
                    'scope'  => 'All API endpoints',
                ],
                'auth' => [
                    'limit'  => 5,
                    'window' => '1 minute',
                    'scope'  => 'POST /register, POST /login',
                    'note'   => 'Strict limit to prevent brute force attacks',
                ],
                'search' => [
                    'limit'  => 30,
                    'window' => '1 minute',
                    'scope'  => 'GET /search, GET /hotels/{id}/availability',
                ],
                'booking' => [
                    'limit'  => 10,
                    'window' => '1 minute',
                    'scope'  => 'POST /bookings, POST /bookings/{id}/cancel',
                ],
                'payment' => [
                    'limit'  => 5,
                    'window' => '1 minute',
                    'scope'  => 'POST /bookings/{id}/pay',
                ],
                'invoice' => [
                    'limit'  => 5,
                    'window' => '1 minute',
                    'scope'  => 'GET/POST /bookings/{id}/invoice/*',
                ],
                'upload' => [
                    'limit'  => 10,
                    'window' => '1 minute',
                    'scope'  => 'POST /manage/hotels/{id}/images',
                ],
                'role_based' => [
                    'admin'       => '300 requests/minute',
                    'hotel_owner' => '120 requests/minute',
                    'guest'       => '60 requests/minute',
                ],
            ],
            'headers' => [
                'X-RateLimit-Limit'     => 'Maximum requests allowed in the window',
                'X-RateLimit-Remaining' => 'Requests remaining in current window',
                'X-RateLimit-Reset'     => 'Unix timestamp when the window resets',
                'Retry-After'           => 'Seconds to wait (only on 429 responses)',
            ],
            'error_format' => [
                'status'  => 429,
                'body'    => [
                    'message'     => 'Description of the limit',
                    'error_code'  => 'RATE_LIMIT_EXCEEDED',
                    'retry_after' => 30,
                    'limit'       => 60,
                ],
            ],
        ]);
    });
});
